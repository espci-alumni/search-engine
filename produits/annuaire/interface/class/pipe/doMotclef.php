<?php

class pipe_doMotclef
{
    protected static $prefix;

    static function php($in, $prefix = '')
    {
        self::$prefix = '<a href="' . patchwork::base($prefix, true) . '?&amp;tag=_';

        return preg_replace_callback("/[^-',\. \(\)]{3,}/u", array(__CLASS__, 'anchor_callback'), $in);
    }

    protected static function anchor_callback($m)
    {
        return self::$prefix . "{$m[0]}\">{$m[0]}</a>";
    }

    static function js()
    {
        ?>/*<script>*/

function($in, $prefix)
{
    $in = '' + $in;
    $prefix = $prefix || '';

    var $kw = $in.match(/[^-',\. \(\)]{3,}/g),
        $out = '', $i = 0, $idx;

    if ($kw) for (; $i < $kw.length; ++$i)
    {
        $idx = $in.indexOf($kw[$i]);

        $out += $in.substr(0, $idx) + '<a href="' + base($prefix, 1) + '?&amp;tag=_' + $kw[$i] + '">' + $kw[$i] + '</a>';

        $in = $in.substr($idx + $kw[$i].length);
    }

    $out += $in;

    return $out;
}

<?php    }
}
