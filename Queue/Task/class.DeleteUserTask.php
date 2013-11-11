<?php
/**
 * Class DeleteUserTask
 */
class DeleteUserTask implements TaskInterface {
	public function process(array $data) {
		// do something ....
		sleep(5);

		throw new Exception('Task not completed!');

		return 'I am processing ...';
	}
}