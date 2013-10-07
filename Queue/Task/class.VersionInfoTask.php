<?php

class VersionInfoTask implements TaskInterface {
	public function process(array $data) {

		// get software info
		$sw = new Software($data['id']);
		$subscribers = $sw->getSubscribers();

		if(!empty($subscribers)) {
			$view 	= new View('VersionInfoEmail.phtml', $sw);
			$view->fileData = $data;

			$msg = new Message();

			foreach ($subscribers as $user) {
				if (!$this->debug) {
					$msg->post($user, $view, 'Notification from ORS about "'.$sw->getName().'"', __METHOD__);
				}
			}
		}
		return true;
	}

}