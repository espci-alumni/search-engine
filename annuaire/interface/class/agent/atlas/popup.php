<?php

class extends agent
{
	public $get = array(
		'zoom:i:1:5' => 1,
		'id:i:1' => 0,
		'p:i:1' => 1,
	);

	protected static $perPage = 5;

	function compose($o)
	{
		$a = "c2.city_id={$this->get->id}";

		switch ($this->get->zoom)
		{
		case 5: $a .= ' AND c1.city_id=c2.city_id';
		case 4: $a .= ' AND c1.city   =c2.city';
		case 3: $a .= ' AND c1.div2   =c2.div2';
		case 2: $a .= ' AND c1.div1   =c2.div1';
		case 1: $a .= ' AND c1.country=c2.country';
		}

		$sql = 'SELECT f.fiche_ref, f.photo_ref, f.nom, f.groupe, f.position
				FROM ' . annuaire::$fiche_table . '
					JOIN ' . annuaire::$city_table . '1 ON c1.city_id=f.city_id
					JOIN ' . annuaire::$city_table . '2 ON ' . $a;

		$a = s::get('atlasResults');
		$sql .= $a && true !== $a ? " WHERE f.fiche_id IN ({$a}) ORDER BY FIELD(f.fiche_id,{$a})" : ' ORDER BY f.mtime DESC';

		$o->fiches = new loop_sql($sql, '', ($this->get->p - 1) * self::$perPage, self::$perPage);

		return $o;
	}
}
