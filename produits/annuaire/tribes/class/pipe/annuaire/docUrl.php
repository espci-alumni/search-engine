<?php

class extends self
{
	static function __constructStatic()
	{
		parent::__constructStatic();
		
		self::$preRef = $CONFIG['tribes.baseUrl'] . 'user/cv/';
	}
}
