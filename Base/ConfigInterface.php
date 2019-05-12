<?php

interface ConfigInterface
{
	// empty for the time being

	public function getUser();

	public function getDB();

	public function mergeConfig($obj);

	public function getLL();
	
}
