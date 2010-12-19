<?php

class extends loop_search
{
	public $resultsPerPage = 10;
	protected $select = 'f.fiche_ref,f.photo_ref,f.nom,f.groupe,f.position,f.extrait,f.doc,f.doc_ref';

	function filterSearch($a)
	{
		if (!empty($a->doc) && false !== strpos($a->doc, '___'))
		{
			$a->doc = preg_replace("'___+'", ' ', $a->doc);
		}

		return $a;
	}
}
