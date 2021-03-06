<?php

spl_autoload_register(function($className) {
    require_once(dirname(__FILE__) . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php');
});

set_exception_handler(function($exception) {
	http_response_code(400);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array(
		'message' => $exception->getMessage(),
		'trace' => _DEBUG ? $exception->getTraceAsString() : ''
	));
});

require_once(dirname(__FILE__) . '/../../config.php');

?>