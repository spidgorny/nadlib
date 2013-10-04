<?php
/**
 * Class DeleteUserTask
 */
class DeleteUserTask extends TaskAbstract {
	public function process(array $data) {
		// Set the status to "IN PROGRESS"
		$this->markInProgress();

		// do something ....
		sleep(10);

		// mark as "DONE"
		$this->markDone();

		return 'I am processing ...';
	}
}