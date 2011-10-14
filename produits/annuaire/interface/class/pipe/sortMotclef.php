<?php

class pipe_sortMotclef
{
    static function php($string)
    {
        $string = urldecode($string);
        $string = explode('_', $string);
        sort($string);

        return implode('_', $string);
    }

    static function js()
    {
        ?>/*<script>*/

function($string)
{
    $string = dUC(str($string));
    $string = $string.split('_');
    $string.sort();

    return $string.join('_');
}

<?php    }
}
