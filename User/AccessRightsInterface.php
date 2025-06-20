<?php

interface AccessRightsInterface
{

	public function can($name);
	public function dehydrate(): array;
	public function setAccess($name, $value): void;


}
