<?php

class pipe_annuaire_docUrl extends self
{
    static function __init()
    {
        parent::__init();

        self::$preRef = $CONFIG['tribes.baseUrl'] . 'user/cv/';
    }
}
