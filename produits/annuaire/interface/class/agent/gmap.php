<?php

class agent_gmap extends agent
{
    const contentType = '';
    protected $maxage = -1;

    function compose($o)
    {
        $o->keys = new loop_array(annuaire::$gmapKeys);

        return $o;
    }
}
