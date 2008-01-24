<?php

class extends loop_sql_fiche
{
	protected

	$select,
	$order_key = 'f.mtime DESC',

	$selectRank = 'SUM(0',
	$selectMatched = 'COUNT(DISTINCT IF(0,0,',
	$selectMatchedEnd = '))',

	$addWhere = '',
	$addCount = 0,

	$delWhere = '',
	$delCount = 0;


	function __construct($query, $subset, $page)
	{
		if ($page <= 0) $page = 1;

		$db = DB();
		$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS searchtmp (
			  fiche_id INT UNSIGNED NOT NULL,
			  order_key INT UNSIGNED NOT NULL auto_increment,
			  PRIMARY KEY (order_key)
			) TYPE=HEAP";
		$db->exec($sql);

		$query = ' ' . lingua::stripAccents($query) . ' ';
		$query = preg_replace_callback("' (\.?[A-Z](?:\.[A-Z])+\.?) 'u", array('lingua', 'acronym_callback'), $query);
		$query = mb_strtolower($query);
		$query = preg_split("' (" . implode('|', array_keys(annuaire::$fieldAlias)) . "):'su", $query, -1, PREG_SPLIT_DELIM_CAPTURE);

		$q = array('' => array_shift($query));

		while ($query) (string) @$q[ annuaire::$fieldAlias[array_shift($query)] ] .= ' ' . array_shift($query);

		array_walk($q, array($this, 'buildQueryComponents'));

		$this->selectRank .= ')';
		$this->selectMatched .= 'NULL' . $this->selectMatchedEnd;

		$sql = "i.mot_id=m.mot_id";

		$this->addWhere = $sql . ($this->addWhere ? " AND (0{$this->addWhere})" : '');
		$this->delWhere = $sql . ($this->delWhere ? " AND (0{$this->delWhere})" : '');

		if ($this->addCount || $this->delCount || $subset)
		{
			$this->hasQuery = true;

			if (!$this->addCount) $this->selectRank = $this->order_key;
			else $this->selectRank .= ' DESC' . ($this->order_key ? ',' . $this->order_key : '');

			$this->addWhere .= ($this->delCount ? " AND i.fiche_id NOT IN (SELECT fiche_id FROM " . annuaire::$mot_fiche_table . ', ' . annuaire::$mot_table . " WHERE {$this->delWhere})" : '')
				. ($subset ? ' AND i.fiche_id=s.fiche_id' : '');

			$db = DB();

			$sql = "INSERT INTO searchtmp
				SELECT i.fiche_id, 0
				FROM " . annuaire::$mot_fiche_table . ', ' . annuaire::$mot_table . ', ' . annuaire::$fiche_table . ($subset ? ', subsettmp s' : '') . "
				WHERE f.fiche_id=i.fiche_id AND {$this->addWhere}
				GROUP BY i.fiche_id";
			if ($this->addCount) $sql .= " HAVING {$this->selectMatched}>" . ($this->addCount > 2 ? '.65' : '.49') . "*{$this->addCount}";
			$sql .= " ORDER BY {$this->selectRank}";

			$db->exec($sql);
		}

		$sql = "SELECT {$this->select}
			FROM " . annuaire::$fiche_table . ", searchtmp s
			WHERE f.fiche_id=s.fiche_id
			ORDER BY s.order_key";

		parent::__construct($sql, array($this, 'filterSearch'), $this->resultsPerPage * ($page - 1), $this->resultsPerPage, $this->highlight);
	}

	function buildQueryComponents($query, $field)
	{
		$q = array('' => array(), ' -' => array());

		preg_replace("'( -)?([a-z0-9]+)'ue", "\$q['$1']['$2']=1", $query);

		if ($q[''])
		{
			$selectRank = '';
			$selectMatched = '';
			$selectMatchedEnd = '';

			$this->highlight[$field] = '';
			$highlight =& $this->highlight[$field];

			foreach (array_keys($q['']) as $k)
			{
				$highlight .= '|' . lingua::getRxQuoteInsensitive($k, '/');

				if (strlen($k) <= 2) $highlight .= '$';

				$this->addCount += 1;

				$if = "m.mot LIKE '{$k}" . (strlen($k) <= 2 ? '' : '%') . "'" . ($field ? " AND i.champ='{$field}'" : '');

				$selectRank .= "+IF({$if}," . strlen($k) . '*i.poids,0)';
				$selectMatched .= "IF({$if},'{$k}',";
				$selectMatchedEnd .= ')';

				$this->addWhere .= " OR ({$if})";
			}

			if (!$highlight) unset($this->highlight[$field]);

			$this->selectRank .= $selectRank;
			$this->selectMatched .= $selectMatched;
			$this->selectMatchedEnd .= $selectMatchedEnd;
		}

		if ($q[' -'])
		{
			foreach (array_keys($q[' -']) as $k)
			{
				$this->delCount += 1;
				$this->delWhere .= " OR (m.mot LIKE '{$k}" . (strlen($k) <= 2 ? '' : '%') . "'" . ($field ? " AND i.champ='{$field}'" : '') . ')';
			}
		}
	}

	function filterSearch($a) {return $a;}

	function addHighlight($fields, $mots)
	{
		if ($mots)
		{
			$fields = (array) $fields;
			$mots = (array) $mots;

			$i = count($mots);
			if ($i) do $mots[--$i] = lingua::getRxQuoteInsensitive($mots[$i], '/') . ('s' == substr($mots[$i], -1) ? '' : 's') . '?$'; while ($i);

			$mots = '|' . implode('|', $mots);

			$i = count($fields);
			if ($i) do @$this->highlight[$fields[--$i]] .= $mots; while ($i);
		}
	}
}
