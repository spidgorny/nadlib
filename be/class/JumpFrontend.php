<?php

class JumpFrontend extends AppControllerBE
{

	public function render()
	{
		$this->request->redirect('../../../../');
	}

}
