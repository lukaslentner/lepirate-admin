<?php

spl_autoload_register(function($className) {
    require_once(dirname(__FILE__) . '/php/' . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php');
});

set_exception_handler(function($exception) {
	http_response_code(400);
	header('Content-Type: application/json');
	echo json_encode(array(
		'error' => $exception->getMessage(),
		'trace' => _DEBUG ? $exception->getTraceAsString() : ''
	));
});

require_once(dirname(__FILE__) . '/../config.php');

?>