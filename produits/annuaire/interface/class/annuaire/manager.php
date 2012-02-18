<?php

class annuaire_manager
{
    protected static

    $fullUpdate = false,

    $needsOptimization = false,
    $lastUpdate,
    $lastRef = false;


    static function __init()
    {
        $db = DB();

        $sql = 'SELECT mtime, fiche_ref FROM fiche ORDER BY mtime DESC LIMIT 1';
        if ($sql = $db->fetchAssoc($sql))
        {
            self::$lastUpdate = $sql['mtime'];
            self::$lastRef = $sql['fiche_ref'];
        }
        else
        {
            self::$lastUpdate = '0000-00-00 00:00:00';
            self::$lastRef = false;
        }
    }

    static function synchronize()
    {
        if ($h = Patchwork::fopenX(PATCHWORK_PROJECT_PATH . 'manager.lock'))
        {
            fclose($h);
            register_shutdown_function('unlink', PATCHWORK_PROJECT_PATH . 'manager.lock');

            set_time_limit(0);
            sleep(1);

            self::removeDeleted();
            self::updateModified();
            self::optimizeDb();
        }
    }

    protected static function removeDeleted()
    {
        user_error('Abstract method, implement me!');
    }

    protected static function updateModified()
    {
        user_error('Abstract method, implement me!');
    }

    protected static function updateFiche($fiche_ref, $fiche, $extrait, $city, $extra)
    {
        self::$needsOptimization = true;

        $db = DB();

        // Récupère l'identifiant interne de la fiche si elle existe
        $sql = 'SELECT fiche_id FROM fiche WHERE fiche_ref=' . $db->quote($fiche_ref);
        $fiche_id = $db->fetchColumn($sql);

        $fiche = (object) $fiche;
        $extrait = self::normalizeExtrait((array) $extrait);
        $city = (object) ($city ? $city : array('city_id' => -1));
        $type = isset($fiche->type) ? (string) $fiche->type : '';

        // Prépare les données à enregistrer sur la fiche
        $data = array(
            'nom' => (string) $fiche->nom,
            'groupe' => (string) $fiche->groupe,
            'position' => (string) $fiche->position,
            'extrait' => serialize($extrait),
            'doc' => (string) $fiche->doc,
            'city_id' => (int) $city->city_id,
            'fiche_ref' => $fiche_ref,
            'photo_ref' => (string) $fiche->photo_ref,
            'doc_ref' => (string) $fiche->doc_ref,
        );

        $type && $data['type'] = $type;

        isset($fiche->mtime) && $data['mtime'] = $fiche->mtime;


        // Complète le référentiel des villes
        if ($city->city_id < 0) unset($data['city_id']);
        else if ($city->city_id && $city->city)
        {
            $sql = array(
                (int) $city->city_id,
                (string) $city->city,
                (float) $city->latitude,
                (float) $city->longitude,
                (string) $city->div1,
                (string) $city->div2,
                (string) $city->country
            );
            $sql = array_map(array($db, 'quote'), $sql);
            $sql = 'INSERT IGNORE INTO city (city_id, city, latitude, longitude, div1, div2, country)
                    VALUES (' . implode(',', $sql) . ')';
            $db->exec($sql);
        }

        // Enregistre la fiche
        if ($fiche_id)
        {
            $is_update = true;
            self::purgeIndex($fiche_id);
            $db->update('fiche', $data, array('fiche_id' => $fiche_id));
        }
        else
        {
            $is_update = false;
            $db->insert('fiche', $data);
            $fiche_id = $db->lastInsertId();
        }


        // Indexe la fiche

        $extrait = array_merge($extrait, $extra, array(
            array('nom', $fiche->nom),
            array('groupe', $fiche->groupe),
            array('position', $fiche->position),
            array('doc', $fiche->doc),
            array('ville', !empty($data['city_id']) ? "{$city->city} {$city->country}" : ''),
        ));

        $fields = array();
        $suggest = array();

        foreach ($extrait as $data) if (is_array($data))
        {
            $field = array_shift($data);
            $extrait = implode(' ', $data);

            isset(annuaire::$fieldAlias[$field]) && $field = annuaire::$fieldAlias[$field];

            if ($poids =& annuaire::$fieldWeight[$field])
            {
                $tag = (int) in_array($field, annuaire::$tagFields);
                isset($fields[$field]) || $fields[$field] = array($poids, '', $tag);
                $fields[$field][1] .= ' ' . $extrait;

                in_array($field, annuaire::$suggestFields) && $suggest[] = array($field, $extrait);
            }
        }

        unset($poids);


        if ($suggest)
        {
            $sql = array();

            foreach ($suggest as $data)
            {
                list($field, $suggest) = $data;
                $field = $db->quote($field);
                $suggest = $db->quote($suggest);
                $sql[] = "({$field},{$suggest},1)";
            }

            $sql = "INSERT INTO suggest
                    VALUES " . implode(',', $sql) . "
                    ON DUPLICATE KEY UPDATE counter=counter+1";
            $db->exec($sql);
        }


        $sql = array();

        if (!empty($city->extra))
        {
            $field = 'ville';

            isset(annuaire::$fieldAlias[$field]) && $field = annuaire::$fieldAlias[$field];

            if (isset($fields[$field]))
            {
                $poids = $fields[$field][0];

                foreach (self::getKeywords($city->extra) as $extrait)
                {
                    self::registerMot($sql, $fiche_id, $extrait, $field, $poids, 0, $type);
                }
            }
        }

        foreach ($fields as $field => $fields)
        {
            list($poids, $extrait, $tag) = $fields;

            foreach (self::getKeywords($extrait) as $extrait)
            {
                self::registerMot($sql, $fiche_id, $extrait, $field, $poids, $tag && strlen($extrait) > 1, $type);
            }
        }

        if ($sql)
        {
            $sql = 'INSERT INTO mot_fiche (fiche_id,mot_id,poids,champ,tag' . ($type ? ',type' : '') . ')
                    VALUES ' . implode(',', $sql);
            $db->exec($sql);
        }

        // Purge le cache
        $is_update && Patchwork::touch('annuaire/fiche/' . $fiche_id);
        Patchwork::touch('annuaire/fiche/0');
    }

    static function deleteFiche($fiche_ref)
    {
        self::$needsOptimization = true;

        $db = DB();

        $sql = 'SELECT fiche_id FROM fiche WHERE fiche_ref=' . $db->quote($fiche_ref);
        if ($fiche_id = $db->fetchColumn($sql))
        {
            self::purgeIndex($fiche_id);

            $db->delete('fiche', array('fiche_id' => $fiche_id));

            $sql = 'DELETE FROM city WHERE city_id NOT IN (SELECT city_id FROM fiche)';
            $db->exec($sql);


            Patchwork::touch('annuaire/fiche/0');
            Patchwork::touch('annuaire/fiche/' . $fiche_id);
        }
    }

    protected static function normalizeExtrait($extrait)
    {
        foreach ($extrait as $k => &$v) if (is_array($v))
        {
            $field = array_shift($v);
            $v = array_map('strval', $v);
            $v = array_map('trim', $v);
            $v = array_diff($v, array(''));
            $v = implode(', ', $v);
            if ('' === $v) unset($extrait[$k]);
            else $v = array($field, $v);
        }

        $extrait = array_values($extrait);

        while ($extrait && !is_array($extrait[0])) array_shift($extrait);
        while ($extrait && !is_array(end($extrait))) array_pop($extrait);

        return $extrait;
    }

    protected static function optimizeDb()
    {
        if (self::$needsOptimization)
        {
            $db = DB();

            $a = 'au aux autour avec chez comme dan de du en entre est et la le mise ne non on ou par pa pour qu quand qui que quel quelle quoi sur un une ainsi alor aussi autre bien ce cet cette comment elle ete etre faire fait il je leur lui mai meme mon no notre nou ont permet peut plu propose sa san se si son sont souvent tout toute tre vou apre celle celui certain donc dont facon lor parfoi prise quelque sera sou ici peuvent avant ci';

            $a = "'" . str_replace(' ', "','", $a) . "','" . str_replace(' ', "s','", $a) . "s'";

            $sql = "SELECT mot_id FROM mot WHERE mot IN ({$a})";
            $sql = "UPDATE mot_fiche SET tag=0 WHERE mot_id IN ({$sql})";
            $db->exec($sql);

            if (!empty(annuaire::$suggestFields))
            {
                $sql = 'DELETE FROM suggest WHERE counter<=0';
                $db->exec($sql);
            }

            $sql = 'DELETE FROM mot WHERE mot_id NOT IN (SELECT mot_id FROM mot_fiche)';
            $db->exec($sql);

            $sql = 'OPTIMIZE TABLE city, fiche, mot, mot_fiche, translation';
            $db->exec($sql);

            self::$needsOptimization = false;
        }
    }

    static function __free()
    {
        self::optimizeDb();
    }

    protected static function purgeIndex($fiche_id)
    {
        $db = DB();

        $sql = "SELECT extrait FROM fiche WHERE fiche_id={$fiche_id}";
        if (!empty(annuaire::$suggestFields) && $extrait = $db->fetchColumn($sql))
        {
            $extrait = unserialize($extrait);

            foreach ($extrait as $extrait) if (is_array($extrait))
            {
                list($field, $data) = $extrait;

                if (!empty(annuaire::$fieldWeight[$field]) && in_array($field, annuaire::$suggestFields))
                {
                    $field = $db->quote($field);
                    $data = $db->quote($data);

                    $sql = "UPDATE suggest SET counter=counter-1
                        WHERE champ={$field} AND suggest=SUBSTRING({$data},1,255)";
                    $db->exec($sql);
                }
            }
        }

        $db->delete('mot_fiche', array('fiche_id' => $fiche_id));
    }

    protected static function getKeywords($mots)
    {
        $mots = lingua::getKeywords($mots);
        return '' !== $mots ? array_unique(explode(' ', $mots)) : array();
    }

    protected static function registerMot(&$registry, $fiche_id, $mot, $field, $poids, $tag, $type)
    {
        if (!$len = strlen($mot)) return;

        $mot_id = 0;
        $db = DB();

        if ('s' == substr($mot, -1))
        {
            if ($len > 3)
            {
                $sql = "SELECT mot_id FROM mot WHERE mot='" . substr($mot, 0, -1) . "'";
                if ($mot_id = $db->fetchColumn($sql))
                {
                    $sql = "UPDATE mot SET tag=mot, mot='{$mot}' WHERE mot_id={$mot_id}";
                    $db->exec($sql);
                }
            }
        }
        else if ($len >= 3)
        {
            $sql = "SELECT mot_id FROM mot WHERE mot='{$mot}s'";
            if ($mot_id = $db->fetchColumn($sql))
            {
                $sql = "UPDATE mot SET tag='{$mot}' WHERE mot_id={$mot_id}";
                $db->exec($sql);
            }
        }

        if (!$mot_id)
        {
            $sql = "SELECT mot_id FROM mot WHERE mot='{$mot}'";
            $mot_id = $db->fetchColumn($sql);
            if (!$mot_id)
            {
                $sql = "INSERT INTO mot (mot) VALUES ('{$mot}')";
                $db->exec($sql);
                $mot_id = $db->lastInsertId();
            }
        }

        $sql = array(
            $fiche_id,
            $mot_id,
            $poids / $len,
            $field,
            $tag,
        );

        $type && $sql[] = $type;

        $sql = array_map(array($db, 'quote'), $sql);
        $registry[] = '(' . implode(',', $sql) . ')';
    }
}
