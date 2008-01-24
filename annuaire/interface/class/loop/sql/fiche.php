<?php

class extends loop_sql
{
	public

	$resultsPerPage,
	$hasQuery = false;

	protected $highlight;


	function __construct($sql, $filter = '', $from = 0, $count = 0, &$highlight = array())
	{
		$this->highlight =& $highlight;
		$this->addFilter($filter);
		parent::__construct($sql, array($this, 'filterFiche'), $from, $count);
	}

	function filterFiche($o)
	{
		$maxLength = $this->hasQuery ? 250 : 100;

		isset($o->extrait) && $o->extrait = highlighter::highlight(unserialize($o->extrait), $this->highlight, $maxLength);

		if (isset($o->doc))
		{
			$maxLength = isset($o->extrait) ? max(100, $maxLength - mb_strlen($o->extrait)) : 100;
			$doc = highlighter::highlight(array(array('doc', $o->doc)), $this->highlight, $maxLength);
			if (false !== strpos($doc, '<')) $o->doc = $doc;
			else unset($o->doc, $o->doc_ref);
		}

		return $o;
	}
}
