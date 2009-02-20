<?php

class extends self
{
	protected static $db, $whereUpdated;


	static function __constructStatic()
	{
		parent::__constructStatic();

		self::$db = DB($CONFIG['annuaire_manager.DSN']);


		self::$whereUpdated = 'admin_confirmed' . (self::$fullUpdate ? '<=' : '>=') . self::$db->quote(self::$lastUpdate);
		!self::$fullUpdate && self::$lastRef && self::$whereUpdated .= ' AND contact_id!=' . self::$db->quote(self::$lastRef);

		self::$whereUpdated = '(' . self::$whereUpdated . ')';
	}

	protected static function deleteFiche($fiche_ref)
	{
		$fiche_ref = (int) $fiche_ref;

		$sql = "SELECT is_active, statut_inscription
				FROM contact_contact
				WHERE contact_id={$fiche_ref}";
		$sql = self::$db->queryOne($sql);

		switch (true)
		{
		case !$sql:
		case !$sql->is_active:
		case 'accepted' !== $sql->statut_inscription:
			parent::deleteFiche($fiche_ref);
		}
	}

	protected static function removeDeleted()
	{
		$sql = 'SELECT contact_id
				FROM contact_contact
				WHERE (NOT is_active OR statut_inscription != "accepted")
					AND ' . self::$whereUpdated;

		$result = self::$db->query($sql);
		while ($row = $result->fetchRow()) parent::deleteFiche($row->contact_id);
	}

	protected static function updateModified()
	{
		$sql = (object) array();

		$sql->nom = 'CONCAT_WS(" ",
			IF(prenom_usuel!="", prenom_usuel, prenom_civil),
			IF(   nom_usuel!="",    nom_usuel,    nom_civil),
			IF(nom_etudiant!="" AND nom_etudiant!=IF(nom_usuel!="",nom_usuel,nom_civil), CONCAT("(",nom_etudiant,")"), NULL)
		)';

		$sql->promo = "''";

		$sql->whereUpdated = self::$whereUpdated;

		$sql = "SELECT
					contact_id,

					photo_token,
					nom_usuel,
					nom_civil,
					nom_etudiant,
					prenom_usuel,
					prenom_civil,
					login,

					{$sql->nom} AS nom,
					{$sql->promo} AS promo

				FROM contact_contact
				WHERE {$sql->whereUpdated} AND is_active AND statut_inscription='accepted'";

		$result = self::$db->query($sql);

		while ($row = $result->fetchRow())
		{
			$fiche_ref = $row->contact_id;

			$fiche = (object) array(
				'nom'       => $row->nom,
				'groupe'    => $row->promo,
				'position'  => '',
				'doc'       => '',
				'photo_ref' => $row->photo_token,
				'doc_ref'   => '',
				'mtime'     => '',
			);

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
		$extrait = array(
			array('email', $row->login . $CONFIG['tribes.emailDomain']),
		);


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
					AND is_active
					AND is_shared
					AND NOT is_obsolete
				ORDER BY sort_key";

		$sql = self::$db->query($sql);

		if ($a = $sql->fetchRow())
		{
			$city = self::geolocalize($a);

			do {
				$extrait[] = ' - ';
				$extrait[] = array('telephone', $a->tel_portable, $a->tel_fixe);
				$extrait[] = array('adresse', $a->adresse);
				$extrait[] = array('ville', "{$a->ville_avant} {$a->ville} {$a->ville_apres}", $a->pays);
			}
			while ($a = $sql->fetchRow());
		}
		else
		{
			$city = false;
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
					fonction,
					secteur,
					date_debut,
					date_fin,
					site_web
				FROM contact_activite ac
				WHERE contact_id={$row->contact_id}
					AND is_shared
					AND NOT is_obsolete
				ORDER BY sort_key";

		$sql = self::$db->query($sql);

		if ($a = $sql->fetchRow())
		{
			$fiche->position = explode(' / ', $a->organisation, 2);
			$fiche->position = $a->fonction . ' - ' . array_shift($fiche->position);

			do {
				$extrait[] = ' - ';

				if ($a->fonction)
				{
					$extrait[] = array('fonction', $a->fonction);
					$extrait[] = ', ';
				}

				$extrait[] = array('entite', $a->service, $a->organisation);

				if ($a->secteur)
				{
					$extrait[] = ', ';
					$extrait[] = array('secteur', $a->secteur);
				}
			}
			while ($a = $sql->fetchRow());
		}


		return $extrait;
	}

	protected static function geolocalize($row)
	{
		$city = (object) array(
			'city_id'   => $row->city_id,
			'city'      => $row->ville,
			'latitude'  => 0,
			'longitude' => 0,
			'div1'      => '',
			'div2'      => '',
			'country'   => $row->pays,
			'extra'     => '',
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
				$city->city      = $row['city'];
				$city->latitude  = $row['latitude'];
				$city->longitude = $row['longitude'];
				$city->div1      = $row['div1'];
				$city->div2      = $row['div2'];
				$city->country   = $row['country'];

				while ($row = $sql->fetch())
				{
					$city->extra .= "{$row['city']} {$row['div1']} {$row['div2']} {$row['country']} ";
				}
			}

		}

		return $city;
	}
}
