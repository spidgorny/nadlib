<?php

class RestController extends AppController {

	function render() {
		$this->request->set('ajax', true);
		$verb = $this->request->getMethod();
		$id = $this->request->getURLLevel(1);
		$data = $this->request->getPOST();

		if ($id) {
			$method = $verb.'1';
		} else {
			$method = $verb;
		}

		if (method_exists($this, $method)) {
			$content = $this->$method($id, $data);
		} else {
			throw new HttpInvalidParamException('Method '.$verb.' not found');
		}

		if (is_array($content)) {
			header('Content-Type: application/json; charset=UTF-8');
			$content = json_encode($content, JSON_PRETTY_PRINT);
		} elseif ($content instanceof OODBase) {
			$content = [
				'status' => 'ok',
				'type' => get_class($content),
				'data' => $content->data,
			];
			header('Content-Type: application/json; charset=UTF-8');
			$content = json_encode($content, JSON_PRETTY_PRINT);
		} elseif ($content instanceof Collection) {
			$content = [
				'status' => 'ok',
				'type' => get_class($content),
				'count' => $content->getCount(),
				'data' => $content->getData(),
			];
			header('Content-Type: application/json; charset=UTF-8');
			$content = json_encode($content, JSON_PRETTY_PRINT);
		}

		return $content;
	}

}
