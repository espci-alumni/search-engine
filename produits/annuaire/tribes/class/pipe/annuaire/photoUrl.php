<?php

class pipe_annuaire_photoUrl extends self
{
    static function __init()
    {
        parent::__init();

        self::$preRef = $CONFIG['tribes.baseUrl'] . 'user/photo/';
    }
}
