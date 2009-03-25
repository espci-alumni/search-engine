<?php

class extends self
{
	function control()
	{
		if (!s::get('contact_id'))
		{
			s::flash('referer', p::__URI__());
			p::redirect('/login');
		}
	}
}
