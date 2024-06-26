<?php

require_once(dirname(__FILE__) . '/../../../php/include.php');

$db = new DB();
$eventsGateway = new EventsGateway($db);

// CORS
header('Access-Control-Allow-Origin: *');

// Cache
header('Cache-Control: no-cache');

if($_SERVER['REQUEST_METHOD'] === 'GET') {
	if(isset($_GET['id'])) {
		if(isset($_GET['iCal'])) {
			$eventsGateway->getICal();
		} else if(isset($_GET['image'])) {
			$eventsGateway->getImage();
		} else if(isset($_GET['image2'])) {
			$eventsGateway->getImage2();
		} else {
			$eventsGateway->get();
		}
	} else if(isset($_GET['iCal'])) {
		$eventsGateway->listComingICal();
	} else {
		$eventsGateway->list();
	}
} else {
	throw new Exception('Unknown HTTP method');
}

?>