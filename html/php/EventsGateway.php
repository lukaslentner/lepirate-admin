<?php

class EventsGateway {
	
	private $schema;
	
	private $db;
	
	private $dbTypes;
	
	function __construct($db) {
		$this->schema = json_decode(file_get_contents(dirname(__FILE__) . '/EventsSchema.json'), FALSE);
		$this->db = $db;
		$this->dbTypes = 'sissssssssss';
    }
	
	function list() {
		
		if(!isset($_GET['year']) || !is_numeric($_GET['year'])) {
			throw new Exception('"year" is not set or is not numeric');
		}
		$year = intval($_GET['year']);

		if(!isset($_GET['month']) || !is_numeric($_GET['month'])) {
			$month = null;
		} else {
			$month = intval($_GET['month']);
		}

		if(!isset($_GET['include'])) {
			$include = '';
		} else {
			$include = $_GET['include'];
		}
		
		$sortDirection = isset($_GET['descending']) ? 'DESC' : 'ASC';

		if($month === null) {
			$events = $this->db->list('Events', $this->columns($include), 'YEAR(`startTime`) = ?', 'i', array($year), 'startTime', $sortDirection);
		} else {
			$events = $this->db->list('Events', $this->columns($include), 'YEAR(`startTime`) = ? AND MONTH(`startTime`) = ?', 'ii', array($year, $month), 'startTime', $sortDirection);
		}
		
		$eventDtos = array_map('self::writeEventDto', $events);

		header('Content-Type: application/json');
		echo json_encode($eventDtos);
		
	}
	
	function get() {
		
		if(!isset($_GET['id'])) {
			throw new Exception('"id" is not set');
		}
		$id = $_GET['id'];

		if(!isset($_GET['include'])) {
			$include = '';
		} else {
			$include = $_GET['include'];
		}
		
		if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && intval($_SERVER['HTTP_IF_NONE_MATCH']) === $this->db->getVersion('Events', $id)) {
			http_response_code(304);
			exit;
		}

		$event = $this->db->get('Events', $this->columns($include), $id);
		
		$eventDto = self::writeEventDto($event);

		header('Content-Type: application/json');
		header('ETag: ' . $event['version']);
		echo json_encode($eventDto);
		
	}
	
	function getImage() {
		
		if(!isset($_GET['id'])) {
			throw new Exception('"id" is not set');
		}
		$id = $_GET['id'];
		
		if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && intval($_SERVER['HTTP_IF_NONE_MATCH']) === $this->db->getVersion('Events', $id)) {
			http_response_code(304);
			exit;
		}

		$event = $this->db->get('Events', array('version', 'image'), $id);
		
		if($event['image'] === null) {
			throw new Exception('No image set');
		}

		$eventImageParts = preg_split('/:|;|,/', $event['image']);
		$imageType = $eventImageParts[1];
		$imageData = base64_decode($eventImageParts[3], TRUE);

		header('Content-Type: ' . $imageType);
		header('ETag: ' . $event['version']);
		echo $imageData;
		
	}
	
	function put() {
		
		$eventDto = json_decode(file_get_contents('php://input'), FALSE);
		
		$validator = new JsonSchema\Validator;
		$validator->validate($eventDto, $this->schema);

		if(!$validator->isValid()) {
			$errorMessage = 'body is not valid:';
			foreach($validator->getErrors() as $error) {
				$errorMessage .= '\n' . $error['property'] . ': ' . $error['message'];
			}
		}
		
		$event = self::readEventDto($eventDto);

		$this->db->put('Events', $this->dbTypes, $event);
		
		echo json_encode(new stdClass());
		
	}
	
	function delete() {
		
		if(!isset($_GET['id'])) {
			throw new Exception('"id" is not set');
		}
		$id = $_GET['id'];
		
		if(!isset($_GET['version']) || !is_numeric($_GET['version'])) {
			throw new Exception('"version" is not set or is not numeric');
		}
		$version = intval($_GET['version']);

		$this->db->delete('Events', $id, $version);
		
		echo json_encode(new stdClass());
		
	}
	
	private function columns($include) {
		$includes = explode(',', $include);
		$columns = array('id', 'version', 'startTime', 'entry', 'title', 'subtitle', 'series');
		if(in_array('content', $includes, TRUE)) {
			$columns = array_merge($columns, array('text', 'lineup', 'notes'));
		}
		if(in_array('image', $includes, TRUE)) {
			$columns = array_merge($columns, array('image'));
		}
		if(in_array('links', $includes, TRUE)) {
			$columns = array_merge($columns, array('links'));
		}
		return $columns;
	}
	
	private static function readEventDto($eventDto) {
		
		$event = array();
		$event['id']        = $eventDto->id;
		$event['version']   = $eventDto->version;
		$event['startTime'] = substr($eventDto->startTime, 0, 10) . ' ' . substr($eventDto->startTime, 11, 5) . ':00';
		$event['entry']     = $eventDto->entry;
		$event['title']     = $eventDto->title;
		$event['subtitle']  = $eventDto->subtitle;
		$event['series']    = $eventDto->series;
		$event['text']      = $eventDto->text;
		$event['lineup']    = $eventDto->lineup;
		$event['notes']     = $eventDto->notes;
		$event['image']     = $eventDto->image;
		$event['links']     = json_encode($eventDto->links);
		
		return $event;
		
	}
	
	private static function writeEventDto($event) {
		
		$eventDto = array();
		$eventDto['id']        = $event['id'];
		$eventDto['version']   = $event['version'];
		$eventDto['startTime'] = substr($event['startTime'], 0, 10) . 'T' . substr($event['startTime'], 11, 5);
		$eventDto['entry']     = $event['entry'];
		$eventDto['title']     = $event['title'];
		$eventDto['subtitle']  = $event['subtitle'];
		$eventDto['series']    = $event['series'];
		
		if(array_key_exists('text', $event)) {
			$eventDto['text']   = $event['text'];
			$eventDto['lineup'] = $event['lineup'];
			$eventDto['notes']  = $event['notes'];
		}
		
		if(array_key_exists('image', $event)) {
			$eventDto['image'] = $event['image'];
		}
		
		if(array_key_exists('links', $event)) {
			$eventDto['links'] = json_decode($event['links'], FALSE);
		}
		
		return $eventDto;
		
	}
	
}

?>