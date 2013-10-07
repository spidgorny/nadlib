<?php

class VersionInfoTask implements TaskInterface {
	public function process(array $data) {

		// get software info
		$sw = new Software($data['id']);
		$subscribers = $sw->getSubscribers();

		if(!empty($subscribers)) {
			$view 	= new View('VersionInfo.phtml', $sw);
			$view->fileData = $data;

			$msg = new Message();

			foreach ($subscribers as $user) {
				if (!$this->debug) {
					$msg->post(new Person(1879959), $view, 'Notification from ORS about "'.$sw->getName().'"', __METHOD__);
				}
			}
		}
		return print_r($subscribers, true);
	}

}