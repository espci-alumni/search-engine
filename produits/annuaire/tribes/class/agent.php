<?php

class agent extends self
{
    function control()
    {
        if (!SESSION::get('acces'))
        {
            SESSION::flash('referer', Patchwork::__URI__());
            Patchwork::redirect($CONFIG['tribes.baseUrl'] . 'login');
        }
    }
}
