<?php

class
{
	const target = '';

	protected static

	$pipe,

	$preRef,
	$postRef = '',
	$preVariant,
	$postVariant,

	$noRef;


	static function __constructStatic()
	{
		isset(self::$preRef) || self::$preRef = substr(__CLASS__, 14, -3) . '/';
		isset(self::$noRef)  || 'photo' === substr(__CLASS__, 14, -3) && self::$noRef = 'img/inconnu.gif';
	}

	static function php($ref, $variant = '')
	{
		$ref = p::string($ref);
		$ref = $ref ? self::$preRef . $ref . self::$postRef : self::$noRef;

		if ($variant = p::string($variant))
		{
			$variant = self::$preVariant . $variant . self::$postVariant;
		}
		else $variant = '';

		$ref = str_replace('%variant%', $variant, $ref);

		return p::base($ref, true);
	}

	static function js()
	{
		?>/*<script>*/

function($ref, $variant)
{
	$ref = str($ref);
	$ref = $ref ? <?php echo jsquote(self::$preRef) ?> + $ref + <?php echo jsquote(self::$postRef) ?> : <?php echo jsquote(self::$noRef) ?>;

	if ($variant = str($variant))
	{
		$variant = <?php echo jsquote(self::$preVariant) ?> + $variant + <?php echo jsquote(self::$postVariant) ?>;
	}
	else $variant = '';

	$ref = $ref.replace(/%variant%/g, $variant);

	return base($ref, 1);
}

window.P$annuaire_ficheUrl_target = <?php echo jsquote(self::target) ?>;

<?php	}
}
