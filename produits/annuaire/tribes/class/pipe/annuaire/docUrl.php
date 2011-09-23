<?php

class pipe_annuaire_docUrl extends self
{
    static function __constructStatic()
    {
        parent::__constructStatic();

        self::$preRef = $CONFIG['tribes.baseUrl'] . 'user/cv/';
    }
}
