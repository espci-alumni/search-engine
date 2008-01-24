<?php

class extends agent
{
	protected $maxage = -1;

	function compose($o)
	{
		// Définition du formulaire de recherche

		$form = new pForm($o, '', false);

		$form->setPrefix('');
		$form->action = p::__BASE__();

		$form->add('text', 'q');


		// Définition des onglets de recherche

		$o->tabs = new loop_array(array(
			array(
				'caption' => T('Annuaire'),
				'url' => '',
			),

			array(
				'caption' => T('Trombi'),
				'url' => 'trombi/',
			),

			array(
				'caption' => T('Atlas'),
				'url' => 'atlas/',
			),

		), 'filter_rawArray');


		return $o;
	}
}
