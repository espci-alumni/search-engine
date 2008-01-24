<?php

class extends agent
{
	const contentType = 'text/javascript';

	function control() {}

	function compose($o)
	{
		pipe_annuaire_ficheUrl::js();
		pipe_annuaire_photoUrl::js();

		return $o;
	}
}
