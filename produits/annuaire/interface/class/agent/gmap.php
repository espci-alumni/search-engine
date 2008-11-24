<?php

class extends agent
{
	const contentType = '';
	protected $maxage = -1;

	function compose($o)
	{
		$o->keys = new loop_array(annuaire::$gmapKeys);

		return $o;
	}
}
