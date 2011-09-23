<?php

class annuaire_manager extends self
{
    protected static $db, $whereUpdated;


    static function __constructStatic()
    {
        parent::__constructStatic();

        self::$db = DB($CONFIG['annuaire_manager.DSN']);


        self::$whereUpdated = 'admin_confirmed' . (self::$fullUpdate ? '<=' : '>=') . self::$db->quote(self::$lastUpdate);

        if (!self::$fullUpdate && self::$lastRef)
        {
            self::$whereUpdated .= ' AND (contact_id!=' . self::$db->quote(self::$lastRef)
                . ' OR admin_confirmed >' . self::$db->quote(self::$lastUpdate)
                . ' OR contact_modified>' . self::$db->quote(self::$lastUpdate)
                . ')';
        }

        self::$whereUpdated = '(' . self::$whereUpdated . ')';
    }

    static function deleteFiche($fiche_ref)
    {
        $fiche_ref = (int) $fiche_ref;

        $sql = "SELECT is_active
                FROM contact_contact
                WHERE contact_id={$fiche_ref}";
        $sql = self::$db->queryOne($sql);

        switch (true)
        {
        case !$sql:
        case !$sql->is_active:
            parent::deleteFiche($fiche_ref);
        }
    }

    protected static function removeDeleted()
    {
        $sql = 'SELECT contact_id
                FROM contact_contact
                WHERE NOT is_active AND ' . self::$whereUpdated;

        $result = self::$db->query($sql);
        while ($row = $result->fetchRow()) parent::deleteFiche($row->contact_id);
    }

    protected static function updateModified()
    {
        $sql = (object) array();

        $sql->nom = 'CONCAT_WS(" ",
            IF(prenom_usuel!="", prenom_usuel, prenom_civil),
            IF( nom_usuel!="", nom_usuel, nom_civil),
            IF(nom_etudiant!="" AND nom_etudiant!=IF(nom_usuel!="",nom_usuel,nom_civil), CONCAT("(",nom_etudiant,")"), NULL)
        )';

        $sql->promo = empty($CONFIG['promoSql']) ? "''" : $CONFIG['promoSql'];
        $sql->email = empty($CONFIG['emailSql']) ? "''" : $CONFIG['emailSql'];

        $sql->whereUpdated = self::$whereUpdated;

        $sql = "SELECT
                    contact_id,

                    photo_token,
                    cv_token,
                    cv_text,

                    nom_usuel,
                    nom_civil,
                    nom_etudiant,
                    prenom_usuel,
                    prenom_civil,
                    login,

                    {$sql->nom} AS nom,
                    {$sql->promo} AS promo,
                    {$sql->email} AS email,

                    GREATEST(admin_confirmed, contact_modified) AS contact_modified,
                    acces
                FROM contact_contact
                WHERE {$sql->whereUpdated} AND is_active AND is_obsolete<=0 AND admin_confirmed";

        $result = self::$db->query($sql);

        while ($row = $result->fetchRow())
        {
            $fiche_ref = $row->contact_id;

            $extra = explode('.', $row->photo_token);
            $row->photo_token = isset($extra[1]) ? $extra[0] . '/' . $row->login . '.' . $extra[1] : false;

            $extra = explode('.', $row->cv_token);
            $row->cv_token = isset($extra[1]) ? $extra[0] . '/' . $row->login . '.' . $extra[1] : false;

            $fiche = (object) array(
                'nom' => $row->nom,
                'groupe' => $row->promo,
                'position' => '',
                'doc' => $row->cv_text,
                'photo_ref' => $row->login && $row->photo_token ? $row->photo_token : '',
                'doc_ref' => $row->login && $row->cv_token ? $row->cv_token : '',
                'mtime' => $row->contact_modified,
            );

            unset($city);
            $extrait = self::buildExtrait($row, $fiche, $city);

            $extra = array(
                array('nom', "{$row->nom_usuel} {$row->nom_civil} {$row->nom_etudiant} {$row->prenom_usuel} {$row->prenom_civil}"),
                array('groupe', ''),
                array('tag', ''),
            );

            self::updateFiche($fiche_ref, $fiche, $extrait, $city, $extra);
        }
    }

    protected static function buildExtrait($row, &$fiche, &$city)
    {
        $extrait = array();

        $extrait[] = array('email', $row->email);

        // Adresses

        $sql = "SELECT
                    tel_portable,
                    tel_fixe,
                    adresse,
                    city_id,
                    ville_avant,
                    ville,
                    ville_apres,
                    pays
                FROM contact_adresse
                WHERE contact_id={$row->contact_id}
                    AND admin_confirmed
                    AND is_shared
                    AND is_obsolete<=0
                    AND description!=''
                ORDER BY sort_key";

        $sql = self::$db->query($sql);

        while ($a = $sql->fetchRow())
        {
            self::buildExtraitAdresse($a, $extrait, $city);
        }

        // Activités

        $sql = "SELECT GROUP_CONCAT(organisation ORDER BY af.sort_key SEPARATOR ' / ')
                FROM contact_organisation o
                    JOIN contact_affiliation af ON af.organisation_id=o.organisation_id AND is_admin_confirmed
                WHERE af.activite_id=ac.activite_id
                GROUP BY ''";

        $sql = "SELECT
                    ({$sql}) AS organisation,
                    service,
                    IF (titre !='',titre ,fonction) AS titre,
                    IF (fonction!='',fonction,titre) AS fonction,
                    secteur,
                    date_debut,
                    date_fin,
                    site_web,
                    keyword,
                    ad.adresse_id,
                    tel_portable,
                    tel_fixe,
                    adresse,
                    city_id,
                    ville_avant,
                    ville,
                    ville_apres,
                    pays
                FROM contact_activite ac
                    LEFT JOIN contact_adresse ad
                        ON ad.adresse_id=ac.adresse_id AND ad.is_shared
                WHERE ac.contact_id={$row->contact_id}
                    AND ac.admin_confirmed
                    AND ac.is_shared
                    AND ac.is_obsolete<=0
                ORDER BY
                    IF(ac.date_fin, ac.date_debut, '9999-12-31') DESC,
                    IF(ac.date_fin, ac.date_fin, ac.date_debut) DESC,
                    ac.activite_id DESC";

        $sql = self::$db->query($sql);

        if ($a = $sql->fetchRow())
        {
            $fiche->position = explode(' / ', $a->organisation, 2);
            $fiche->position = ($a->titre ? $a->titre . ' - ' : '') . array_shift($fiche->position);

            do {
                self::buildExtraitActivite($a, $extrait, $city);

                if ($a->adresse_id)
                {
                    self::buildExtraitAdresse($a, $extrait, $city);
                }
            }
            while ($a = $sql->fetchRow());
        }

        return $extrait;
    }

    protected static function buildExtraitAdresse($a, &$extrait, &$city)
    {
        if (!$city)
        {
            $city = self::geolocalize($a);
            $city->city_id || $city = false;
        }

        $extrait[] = ' - ';
        $extrait[] = array('telephone', $a->tel_portable, $a->tel_fixe);
        $extrait[] = array('adresse', $a->adresse);
        $extrait[] = ($a->tel_portable || $a->tel_fixe || $a->adresse ? ' - ' : '') . $a->ville_avant . ' ';
        $extrait[] = array('ville', $a->ville);
        $extrait[] = $a->ville_apres . ($a->pays ? ', ' : '');
        $extrait[] = array('ville', $a->pays);
    }

    protected static function buildExtraitActivite($a, &$extrait, &$city)
    {
        $extrait[] = ' - ';

        if ($a->titre)
        {
            $extrait[] = array('fonction', $a->titre . ($a->fonction != $a->titre ? " ({$a->fonction})" : ''));
            $extrait[] = ', ';
        }

        $extrait[] = array('entite', $a->service, $a->organisation);

        if ($a->secteur)
        {
            $extrait[] = ', ';
            $extrait[] = array('secteur', $a->secteur);
        }

        if ($a->keyword)
        {
            $extrait[] = ', ';
            $extrait[] = array('tag', $a->keyword);
        }
    }

    protected static function geolocalize($row)
    {
        $city = (object) array(
            'city_id' => $row->city_id,
            'city' => $row->ville,
            'latitude' => 0,
            'longitude' => 0,
            'div1' => '',
            'div2' => '',
            'country' => $row->pays,
            'extra' => '',
        );

        if ($city->city_id)
        {
            $sql = "SELECT city,
                        latitude,
                        longitude,
                        country,
                        div1,
                        div2
                FROM city c JOIN region r
                    ON r.region_id=c.region_id
                WHERE city_id={$city->city_id}";
            $sql = geodb::$db->unbufferedQuery($sql, SQLITE_ASSOC);

            if ($row = $sql->fetch())
            {
                $city->city = $row['city'];
                $city->latitude = $row['latitude'];
                $city->longitude = $row['longitude'];
                $city->div1 = $row['div1'];
                $city->div2 = $row['div2'];
                $city->country = $row['country'];

                while ($row = $sql->fetch())
                {
                    $city->extra .= "{$row['city']} {$row['div1']} {$row['div2']} {$row['country']} ";
                }
            }

        }

        return $city;
    }
}
