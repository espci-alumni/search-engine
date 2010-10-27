<?php

class
{
	static function php($string)
	{
		$string = urldecode(p::string($string));
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

<?php	}
}
