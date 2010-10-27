<?php

class
{
	static function php($a, $ref, $attr = '')
	{
		pipe_annuaire_ficheUrl::target && $attr .= ' target="' . pipe_annuaire_ficheUrl::target . '"';
		return '<a href="' . pipe_annuaire_ficheUrl::php($ref) . '" ' . $attr . '>' . p::string($a) . '</a>';
	}

	static function js()
	{
		?>/*<script>*/

function($a, $ref, $attr)
{
	var ficheUrl = <?php pipe_annuaire_ficheUrl::js(); ?>;
	$attr = str($attr)<?php if (pipe_annuaire_ficheUrl::target) echo " + ' target=\"" . pipe_annuaire_ficheUrl::target . "\"'"; ?>;
	return '<a href="' + ficheUrl($ref) + '" ' + $attr + '>' + str($a) + '</a>';
}

<?php	}
}
