<?php

class agent extends self
{
    function control()
    {
        if (!SESSION::get('acces'))
        {
            SESSION::flash('referer', patchwork::__URI__());
            patchwork::redirect($CONFIG['tribes.baseUrl'] . 'login');
        }
    }
}
