<?php
/**
 * Class NotifyUserTask
 */
class NotifyUserTask implements TaskInterface {
	public function process(array $data) {
		// do something ....
		sleep(5);

		return 'I am processing ...';
	}
}