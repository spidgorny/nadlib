<?php

class RestController extends AppControllerBE
{

	public function loginBasic(): void
	{
		$this->request->set('ajax', true);
		$login = ifsetor($_SERVER['PHP_AUTH_USER']);
		$password = ifsetor($_SERVER['PHP_AUTH_PW']);
		$auth = Config::getInstance()->getAuth();
		$status = $auth->login($login, $password);
		//pre_print_r($status);
		if ($status['error']) {
			throw new AccessDeniedException();
		}

		$_COOKIE[$auth->config->cookie_name] = $status['hash'];
		$this->user->login();  // again
	}

	public function render(): string|false
	{
		$this->request->set('ajax', true);
		$verb = $this->request->getMethod();
		$id = $this->request->getURLLevel(1);
		$data = $this->request->getPOST();

		$method = $id ? $verb . '1' : $verb;

		if (method_exists($this, $method)) {
            $content = $id ? $this->$method($id, $data) : $this->$method($data);
        } else {
			throw new HttpInvalidParamException('Method ' . $verb . ' not found');
		}

		if (is_array($content)) {
			if (!headers_sent()) {
				header('Content-Type: application/json; charset=UTF-8');
			}
            
			$content = json_encode($content, JSON_PRETTY_PRINT);
		} elseif ($content instanceof OODBase) {
			$content = [
				'status' => 'ok',
				'type' => get_class($content),
				'data' => $content->data,
			];
			if (!headers_sent()) {
				header('Content-Type: application/json; charset=UTF-8');
			}
            
			$content = json_encode($content, JSON_PRETTY_PRINT);
		} elseif ($content instanceof Collection) {
			$content = [
				'status' => 'ok',
				'type' => get_class($content),
				'count' => $content->getCount(),
				'data' => $content->getData(),
			];
			if (!headers_sent()) {
				header('Content-Type: application/json; charset=UTF-8');
			}
            
			$content = json_encode($content, JSON_PRETTY_PRINT);
		} else {
			//pre_print_r($this->request->getURLLevels(), $id, $data);
			//throw new HttpInvalidParamException('Unknown method/action');
			$content .= '';
		}

		return $content;
	}

	/**
     * Returns documentation - what can be done by this end-point
     */
    public function OPTIONS(): array
	{
		$allows = [];
		$about = [];
		$rc = new ReflectionClass($this);
		foreach ($rc->getMethods() as $method) {
			if ($method->getName() === strtoupper($method->getName())) {
				$allows[] = str_replace('1', '', $method->getName());
				$dc = new DocCommentParser();
				$dc->parseDocComment($method->getDocComment());
				$about[$method->getName()] = [
					'description' => $dc->getDescription(),
					'parameters' => $method->getParameters(),
				];
			}
		}
        
		header('Allow: ' . implode(', ', $allows));
		return $about;
	}

}
