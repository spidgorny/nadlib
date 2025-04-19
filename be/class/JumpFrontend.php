<?php

class JumpFrontend extends AppControllerBE {

	public function render(): void {
		$this->request->redirect('../../../../');
	}

}
