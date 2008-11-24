<?php

class
{
	static function php($a, $ref, $attr = '')
	{
		return '<a href="' . pipe_annuaire_ficheUrl::php($ref) . '"' . (pipe_annuaire_ficheUrl::target ? ' target="' . pipe_annuaire_ficheUrl::target . '"' : '') . ' ' . $attr . '>' . p::string($a) . '</a>';
	}

	static function js()
	{
		pipe_annuaire_ficheUrl::js();

		?>/*<script>*/

P$annuaire_ficheLink = function($a, $ref, $attr)
{
	return '<a href="' + P$annuaire_ficheUrl($ref) + '"' + (P$annuaire_ficheUrl.target ? ' target="' + P$annuaire_ficheUrl.target + '"' : '') + ' ' + str($attr) + '>' + str($a) + '</a>';
}

<?php	}
}
