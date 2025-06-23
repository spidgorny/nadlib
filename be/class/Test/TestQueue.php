<?php

class TestQueue extends AppControllerBE
{

	public function render()
	{
		return $this->performAction();
	}

	public function processDeleteUserAction(): string
	{
		return $this->processTask('DeleteUser');
	}

	private function processTask(string $type): string
	{
		$content = '';
		$counter = 0;
		$msgQ = new MessageQueue($type);

		while ($taskObj = $msgQ->getTaskObject()) {
			try {
				$content .= '<pre>' . ++$counter . '  ' . $taskObj->process($msgQ->getTaskData()) . '</pre>';
				$msgQ->setStatus(MessageQueue::STATUS_DONE);

			} catch (Exception $e) {
				$msgQ->setStatus(MessageQueue::STATUS_FAILED);
			}
		}

		return $content;
	}

	public function processNotifyUserAction(): string
	{
		return $this->processTask('NotifyUser');
	}

	public function createAction(): void
	{
		$taskData = json_decode('{"array":[1,2,3],"boolean":true,"null":null,"number":123,"object":{"a":"b","c":"d","e":"f"},"string":"Hello World"}', true, 512, JSON_THROW_ON_ERROR);

		$msgQ = new MessageQueue('DeleteUser');
		$msgQ->push($taskData);
	}
}
