<?php

class extends self
{
	function control()
	{
		if (!s::get('acces'))
		{
			s::flash('referer', p::__URI__());
			p::redirect('/login');
		}
	}
}
