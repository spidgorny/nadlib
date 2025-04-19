<?php

class JQueryHTML extends HTML
{

	public function error($content): string
	{
		return '<div class="ui-state-error">' . $this->s($content) . '</div>';
	}

}
