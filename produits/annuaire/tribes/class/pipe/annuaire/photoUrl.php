<?php

class pipe_annuaire_photoUrl extends self
{
    static function __constructStatic()
    {
        parent::__constructStatic();

        self::$preRef = $CONFIG['tribes.baseUrl'] . 'user/photo/';
    }
}
