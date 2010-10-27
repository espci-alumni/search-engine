<?php

class
{
	static function php($s)
	{
		return htmlspecialchars_decode(htmlentities(p::string($s), ENT_NOQUOTES, 'UTF-8'), ENT_NOQUOTES);
	}

	static function js()
	{
		echo 'function($s) {return str($s);}';
	}
}
