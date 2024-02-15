<?php

interface ConfigInterface
{

	public function getUser();

	public function setUser(UserModelInterface $user);

	public function getDB();

	public function mergeConfig($obj);

	public function getLL();

	public function getDefaultController();

}
