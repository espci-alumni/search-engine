<?php

class pipe_annuaire_utf82html
{
    static function php($s)
    {
        return htmlspecialchars_decode(htmlentities($s, ENT_NOQUOTES, 'UTF-8'), ENT_NOQUOTES);
    }

    static function js()
    {
        echo 'function($s) {return str($s);}';
    }
}
