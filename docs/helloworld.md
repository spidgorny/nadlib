# Hello World

First things first. Hello World.

	require_once 'nadlib/init.php';
	$n = new InitNADLIB();
	$n->init();

	echo 'Hello World', BR;
	echo new HTMLTag('a', array(
		'href' => 'http://gooogle.com/',
	), 'Some<XSS>');

It generates:

	Hello World<br />
	<a href="http://google.com/">Some&lt;XSS&gt;</a>
	
