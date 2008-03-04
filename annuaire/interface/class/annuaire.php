<?php

class
{
	static

	$gmapKeys = array(
		'localhost' => 'ABQIAAAA7PGhw3QqnF9_mRptZlvjXhT2yXp_ZAY8_ufC3CFXhHIE1NvwkxQNnvUHdPt5K-h894zG7b2V4HjhDw',
	),

	$city_table      = 'city c',
	$fiche_table     = 'fiche f',
	$mot_table       = 'mot m',
	$mot_fiche_table = 'mot_fiche i',

	$fieldWeight = array(
		'nom'       => .75,
		'groupe'    => .75,
		'entite'    => .625,
		'motclef'   => .625,
		'ville'     => .5,
		'doc'       => .5,
		'adresse'   => .5,
		'telephone' => .5,
	),

	$fieldAlias = array(
		'groupe'	=> 'groupe',
		'group'		=> 'groupe',
		'club'		=> 'groupe',
		'promo'		=> 'groupe',
		'promotion'	=> 'groupe',
		'class'		=> 'groupe',
		'year'		=> 'groupe',

		'nom'		=> 'nom',
		'prenom'	=> 'nom',

		'naissance'	=> 'naissance',
		'anniversaire'	=> 'naissance',
		'anniv'		=> 'naissance',

		'telephone'	=> 'telephone',
		'cell'		=> 'telephone',
		'mobile'	=> 'telephone',
		'portable'	=> 'telephone',
		'tel'		=> 'telephone',

		'email'	=> 'email',
		'mail'	=> 'email',

		'adresse'	=> 'adresse',
		'address'	=> 'adresse',
		'zipcode'	=> 'adresse',
		'codepostal'	=> 'adresse',

		'ville'		=> 'ville',
		'etat'		=> 'ville',
		'pays'		=> 'ville',
		'city'		=> 'ville',
		'state'		=> 'ville',
		'country'	=> 'ville',
		'departement'	=> 'ville',
		'department'	=> 'ville',
		'region'	=> 'ville',

		'entite'	=> 'entite',
		'position'	=> 'entite',
		'institution'	=> 'entite',
		'ecole'		=> 'entite',
		'societe'	=> 'entite',
		'entreprise'	=> 'entite',
		'organisme'	=> 'entite',
		'division'	=> 'entite',
		'service'	=> 'entite',
		'laboratoire'	=> 'entite',
		'labo'		=> 'entite',
		'fonction'	=> 'entite',
		'prof'		=> 'entite',
		'profession'	=> 'entite',
		'activite'	=> 'entite',

		'motclef'	=> 'motclef',
		'motcle'	=> 'motclef',
		'mot'		=> 'motclef',
		'tag'		=> 'motclef',

		'perso'	=> 'perso',
		'autre'	=> 'perso',

		'doc'	=> 'doc',
		'cv'	=> 'doc',
	),

	$tagFields = array(
		'entite',
		'position',
		'ville',
		'motclef',
	),
	
	$tagSizeNb = 5,
	$tagMinNb = 60,
	$tagMaxNb = 80;
}
