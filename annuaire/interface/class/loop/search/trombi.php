<?php

class extends loop_search
{
	public $resultsPerPage = 80;
	protected $select = 'f.fiche_id,f.fiche_ref,f.photo_ref,f.nom';
}
