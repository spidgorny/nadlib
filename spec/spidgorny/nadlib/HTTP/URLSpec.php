<?php

namespace spec\spidgorny\nadlib\HTTP;

use PhpSpec\ObjectBehavior;
use spidgorny\nadlib\HTTP\URL;

class URLSpec extends ObjectBehavior
{
	public function it_is_initializable(): void
	{
		$this->shouldHaveType(URL::class);
	}

	public function it_appends_string_to_path_in_url(): void
	{
		$this->beConstructedWith("https://asd.com/");
		$this->appendString('/HealthCheck');
		$this->toString()->shouldBe('https://asd.com/HealthCheck');
	}

}
