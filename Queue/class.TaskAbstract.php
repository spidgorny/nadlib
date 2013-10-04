<?php
/**
 * Class TaskAbstract
 */

abstract class TaskAbstract implements TaskInterface {

	/**
	 * An instance of MessageQueue
	 * @var MessageQueue
	 */
	protected $messageQueue = null;

	public function __construct(MessageQueue $messageQueue) {
		$this->messageQueue = $messageQueue;
	}

	public function markInProgress() {
		$this->messageQueue->setStatus(MessageQueue::STATUS_IN_PROGRESS);

	}

	public function markDone() {
		$this->messageQueue->setStatus(MessageQueue::STATUS_DONE);
	}
}