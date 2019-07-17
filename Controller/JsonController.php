<?php

trait JsonController
{

    public function afterConstruct()
    {
        $request = Request::getInstance();
        $request->set('ajax', true);
    }

    public function __invoke()
    {
//        debug($_SERVER);
        $requestURI = ifsetor($_SERVER['REQUEST_URI']);
        $url = new \spidgorny\nadlib\HTTP\URL($requestURI);
        $levels = $url->getPath()->getLevels();

        // next after /API/
        //llog(get_class($this));
        $last = null;
        $arguments = [];
        foreach ($levels as $i => $el) {
            if ($el == get_class($this)) {
                $last = ifsetor($levels[$i+1]);
                $arguments = array_slice($levels, $i+2);    // rest are args
                break;
            }
        }
        if (!$last) {
            $last = last($levels);
        }
        $request = trim($last, '/\\ ');
        return call_user_func_array([$this, $request], $arguments);
    }

    public function error(Exception $e)
    {
        $message = '[' . get_class($e) . ']' . PHP_EOL . $e->getMessage() . PHP_EOL . $e->getFile() . '#' . $e->getLine();
        llog($message);
        http_response_code(500);
        header('Content-Type: application/json');
        return json_encode([
            'status' => 'error',
            'error_type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'request' => $_REQUEST,
            'headers' => getallheaders(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    public function json($key)
    {
        header('Content-Type: application/json');
        return json_encode($key, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

}