<?php

class
{
	static function updateFiche($fiche_ref, $fiche, $extrait, $city, $extra)
	{
		$db = DB();

		// Récupère l'identifiant interne de la fiche si elle existe
		$sql = 'SELECT fiche_id FROM fiche WHERE fiche_ref=' . $db->quote($fiche_ref);
		$fiche_id = $db->queryOne($sql);

		$fiche   = (object) $fiche;
		$extrait = self::normalizeExtrait((array) $extrait);
		$city    = (object) $city;

		// Prépare les données à enregistrer sur la fiche
		$data = array(
			'nom'       => (string) $fiche->nom,
			'groupe'    => (string) $fiche->groupe,
			'position'  => (string) $fiche->position,
			'extrait'   => serialize($extrait),
			'doc'       => (string) $fiche->doc,
			'city_id'   => (int) $city->city_id,
			'fiche_ref' => $fiche_ref,
			'photo_ref' => (string) $fiche->photo_ref,
			'doc_ref'   => (string) $fiche->doc_ref,
		);

		isset($fiche->mtime) && $data['mtime'] = $fiche->mtime;


		// Complète le référentiel des villes
		if (!$city->city_id || !$city->city) unset($data['city_id']);
		else
		{
			$sql = array(
				(int)    $city->city_id,
				(string) $city->city,
				(float)  $city->latitude,
				(float)  $city->longitude,
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
			$db->autoExecute('fiche', $data, MDB2_AUTOQUERY_UPDATE, 'fiche_id=' . $fiche_id);
		}
		else
		{
			$is_update = false;
			$db->autoExecute('fiche', $data);
			$fiche_id = $db->lastInsertID();
		}


		// Indexe la fiche

		$extrait = array_merge($extrait, $extra, array(
			array('nom'     , $fiche->nom),
			array('groupe'  , $fiche->groupe),
			array('position', $fiche->position),
			array('doc'     , $fiche->doc),
			array('city'    , "{$city->city} {$city->div1} {$city->div2} {$city->country}"),
		));

		$fields = array();

		foreach ($extrait as $data) if (is_array($data))
		{
			list($field, $extrait) = $data;

			if ($poids =& annuaire::$fieldWeight[$field])
			{
				$tag = (int) in_array($field, annuaire::$tagFields);
				$field = annuaire::$fieldAlias[$field];
				isset($fields[$field]) || $fields[$field] = array($poids, '', $tag);
				$fields[$field][1] .= ' ' . $extrait;
			}
		}

		unset($poids);

		foreach ($fields as $field => $fields)
		{
			list($poids, $extrait, $tag) = $fields;

			foreach (self::getKeywords($extrait) as $extrait)
			{
				self::registerMot($fiche_id, $extrait, $field, $poids, $tag && strlen($extrait) > 1);
			}
		}

		// Purge le cache
		$is_update && p::touch('annuaire/fiche/' . $fiche_id);
		p::touch('annuaire/fiche/0');
	}

	static function deleteFiche($fiche_ref)
	{
		$db = DB();

		$sql = 'SELECT fiche_id FROM fiche WHERE fiche_ref=' . $db->quote($fiche_ref);
		if ($fiche_id = $db->queryOne($sql))
		{
			self::purgeIndex($fiche_id);

			$sql = "DELETE FROM fiche WHERE fiche_id={$fiche_id}";
			$db->exec($sql);

			$sql = 'DELETE FROM city WHERE city_id NOT IN (SELECT city_id FROM fiche)';
			$db->exec($sql);


			p::touch('annuaire/fiche/0');
			p::touch('annuaire/fiche/' . $fiche_id);
		}
	}

	static function normalizeExtrait($extrait)
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

		return array_values($extrait);
	}

	protected static function purgeIndex($fiche_id)
	{
		$db = DB();

		$sql = "DELETE FROM mot_fiche WHERE fiche_id={$fiche_id}";
		$db->exec($sql);

		$sql = 'DELETE FROM mot WHERE mot_id NOT IN (SELECT mot_id FROM mot_fiche)';
		$db->exec($sql);
	}

	protected static function getKeywords($mots)
	{
		$mots = lingua::getKeywords($mots);
		$mots = preg_replace("'(?: (?:the|and|for|from|with|des?|du|les?|la|un|une|sur|aux?|par|avec|dans|pour|https?|autre|en|qu[ei]?))* 'su", ' ', $mots);
		$mots = preg_replace("'(?: ..?)* 'su", ' ', $mots);
		return $mots !== '' ? array_unique(explode(' ', $mots)) : array();
	}

	protected static function registerMot($fiche_id, $mot, $field, $poids, $tag)
	{
		if (!$len = strlen($mot)) continue;

		$mot_id = 0;
		$db = DB();

		if ('s' == substr($mot, -1))
		{
			if ($len > 3)
			{
				$sql = "SELECT mot_id FROM mot WHERE mot='" . substr($mot, 0, -1) . "'";
				if ($mot_id = $db->queryOne($sql))
				{
					$sql = "UPDATE mot SET tag=mot, mot='{$mot}' WHERE mot_id={$mot_id}";
					$db->exec($sql);
				}
			}
		}
		else if ($len >= 3)
		{
			$sql = "SELECT mot_id FROM mot WHERE mot='{$mot}s'";
			if ($mot_id = $db->queryOne($sql))
			{
				$sql = "UPDATE mot SET tag='{$mot}' WHERE mot_id={$mot_id}";
				$db->exec($sql);
			}
		}

		if (!$mot_id)
		{
			$sql = "SELECT mot_id FROM mot WHERE mot='{$mot}'";
			$mot_id = $db->queryOne($sql);
			if (!$mot_id)
			{
				$sql = "INSERT INTO mot (mot) VALUES ('{$mot}')";
				$db->exec($sql);
				$mot_id = $db->lastInsertID();
			}
		}

		$db->autoExecute(
			'mot_fiche',
			array(
				'fiche_id' => $fiche_id,
				'mot_id' => $mot_id,
				'poids' => $poids / $len,
				'champ' => $field,
				'tag' => $tag,
			)
		);
	}
}
