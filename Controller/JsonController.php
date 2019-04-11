<?php

class JsonController
{

    public function __construct()
    {
    }

    public function __invoke()
    {
//        debug($_SERVER);
        $request = ifsetor($_SERVER['REQUEST_URI']);
        $url = new \spidgorny\nadlib\HTTP\URL($request);
        $request = trim($url->getPath(), '/\\ ');
        return call_user_func([$this, $request]);
    }

    public function error(Exception $e)
    {
        $message = '[' . get_class($e) . ']' . PHP_EOL . $e->getMessage() . PHP_EOL . $e->getFile() . '#' . $e->getLine();
        error_log($message);
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
        ], JSON_PRETTY_PRINT
        );
    }

    public function json($key)
    {
        header('Content-Type: application/json');
        return json_encode($key);
    }

}