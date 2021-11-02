<?php

require_once(dirname(__FILE__) . '/../../include.php');

$oAuth = new OAuth();
$db = new DB();
$eventsGateway = new EventsGateway($db);

// Cache
header('Cache-Control: no-cache');

if($_SERVER['REQUEST_METHOD'] === 'GET') {
	if(isset($_GET['id'])) {
		if(isset($_GET['image'])) {
			$eventsGateway->getImage();
		} else {
			$eventsGateway->get();
		}
	} else {
		$eventsGateway->list();
	}
} else if($_SERVER['REQUEST_METHOD'] === 'PUT') {
	$eventsGateway->put();
} else if($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	$eventsGateway->delete();
} else {
	throw new Exception('Unknown HTTP method');
}

?>