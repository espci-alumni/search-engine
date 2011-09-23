<?php

class agent_atlas extends agent_index
{
    protected $loopFiche = 'loop_search_atlas';
    protected $displayAll = false;

    function prepareResults($o)
    {
        if ('' === trim($this->get->q)) $this->displayAll = true;
        else
        {
            $o = parent::prepareResults($o);
            unset($o->fiches);
        }

        return $o;
    }

    function filterResults($o)
    {
        $db = DB();

        if ($this->displayAll)
        {
            SESSION::set('atlasResults', true);

            $sql = 'f.city_id!=0';
        }
        else
        {
            $sql = 'DELETE FROM searchtmp WHERE fiche_id IN (SELECT fiche_id FROM ' . annuaire::$fiche_table . ' WHERE city_id=0)';
            $db->exec($sql);

            $sql = "SELECT GROUP_CONCAT(fiche_id ORDER BY order_key SEPARATOR ',') FROM searchtmp GROUP BY ''";
            $sql = $db->queryOne($sql);
            SESSION::set('atlasResults', $sql ? $sql : '0');

            $sql = 'f.fiche_id IN (SELECT fiche_id FROM searchtmp)';

            $o = parent::filterResults($o);
        }

        $sql = 'SELECT MAX(latitude) AS max_lat, MIN(latitude) AS min_lat, MAX(longitude) AS max_lng, MIN(longitude) AS min_lng, VARIANCE(longitude) AS var_lng
                FROM ' . annuaire::$city_table . ' JOIN ' . annuaire::$fiche_table . ' ON f.city_id=c.city_id
                WHERE ' . $sql;
        foreach ($db->queryRow($sql) as $k => $v) $o->$k = $v;

        return $o;
    }

    function prepareFilter($o) {return $o;}
}
