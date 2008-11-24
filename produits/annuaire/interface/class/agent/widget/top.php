<?php

class extends agent
{
	public $get = array('type:c:random|mtime' => 'mtime');

	protected static $limit = 5;


	function compose($o)
	{
		switch ($this->get->type)
		{
		case 'random':

			$this->expires = 'onmaxage';
			$this->maxage = 300;

			$sql = 'SELECT COUNT(*) AS nb, MAX(fiche_id) AS max_id FROM ' . annuaire::$fiche_table;
			$bound = DB()->queryRow($sql);

			$bound->nb && $bound->nb = (int) (self::$limit * (10 * ($bound->max_id / $bound->nb - 1) + 1));
			$bound->nb > $bound->max_id && $bound->nb = $bound->max_id;

			$end = array();

			do $end[rand(1, $bound->max_id)] = 1;
			while ($bound->nb > count($end));

			$end = implode(',', array_keys($end));
			$end = "WHERE fiche_id IN ({$end}) ORDER BY FIELD(fiche_id,{$end})";
			break;

		case 'mtime':
		default:

			$this->maxage = 3600;
			$this->watch[] = 'fiche/0';

			$end = 'ORDER BY mtime DESC';
			break;
		}

		$sql = 'SELECT fiche_ref, nom, groupe, position FROM ' . annuaire::$fiche_table . ' ' . $end;
		$o->TOP = new loop_sql_fiche($sql, '', 0, self::$limit);

		return $o;
	}
}
