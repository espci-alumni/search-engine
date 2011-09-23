<?php

class agent_atlas_marks extends agent
{
    public $get = array(
        'zoom:i:1:5' => 1,
        'mnLt:f:-90:90' => -90,
        'mxLt:f:-90:90' => 90,
        'mnLg:f:-180:180' => -180,
        'mxLg:f:-180:180' => 180,
    );

    function compose($o)
    {
        $sql = 'IF((VARIANCE(longitude)!=0 OR VARIANCE(latitude)!=0) AND ""!=div';

        switch ($this->get->zoom)
        {
        case 5: $sql = $this->buildGeoQuery(5, 'city', 'country,div1,div2,city,c.city_id'); break;
        case 4: $sql = $this->buildGeoQuery(4, 'city', 'country,div1,div2,city'); break;
        case 3: $sql = $this->buildGeoQuery('IF(div2!="",3,4)', $sql.'2,CONCAT_WS(", ",div2,div1,country),city)', 'country,div1,div2,IF(div2!="",0,city)'); break;
        case 2: $sql = $this->buildGeoQuery('IF(div1!="",2,4)', $sql.'1,CONCAT_WS(", ", div1,country),city)', 'country,div1, IF(div1!="",0,city)'); break;
        case 1:
        default: $sql = $this->buildGeoQuery(1, 'country', 'country');
        }

        $o->marks = new loop_sql($sql);

        return $o;
    }

    protected function buildGeoQuery($zoom, $label, $groupBy)
    {
        $sql = SESSION::get('atlasResults');
        $sql = !$sql || true === $sql ? 'f.city_id!=0' : "f.fiche_id IN ({$sql})";

        $sql = "SELECT
                    {$label} AS label,
                    ROUND(AVG(longitude)*1000 + 180000) AS lng,
                    ROUND(AVG(latitude)*1000 + 90000) AS lat,
                    COUNT(*) AS nb,
                    c.city_id AS id,
                    {$zoom} AS zoom
                FROM " . annuaire::$city_table . ' JOIN ' . annuaire::$fiche_table . " ON f.city_id=c.city_id
                WHERE ({$sql})
                    AND latitude BETWEEN {$this->get->mnLt} AND {$this->get->mxLt}
                    AND " . ($this->get->mnLg < $this->get->mxLg
                        ? "longitude BETWEEN {$this->get->mnLg} AND {$this->get->mxLg}"
                        : "NOT (longitude BETWEEN {$this->get->mxLg} AND {$this->get->mnLg})"
                    ) . "
                GROUP BY {$groupBy}";

        return $sql;
    }
}
