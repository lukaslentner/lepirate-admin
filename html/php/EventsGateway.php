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
			$events = $this->db->list('Events', self::columns($include), 'YEAR(`startTime`) = ?', 'i', array($year), 'startTime', $sortDirection);
		} else {
			$events = $this->db->list('Events', self::columns($include), 'YEAR(`startTime`) = ? AND MONTH(`startTime`) = ?', 'ii', array($year, $month), 'startTime', $sortDirection);
		}
		
		$eventDtos = array_map('self::writeEventDto', $events);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($eventDtos);
		
	}
	
	function listComingICal() {
		
		$events = $this->db->list('Events', self::columns(), '`startTime` > NOW()', '', array(), 'startTime', 'ASC');
		
		$iCal = self::writeICalEvents($events);

		header('Content-Type: text/calendar; charset=utf-8');
		header('Content-Disposition: attachment; filename="cal.ics"');
		echo $iCal;
		
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

		$event = $this->db->get('Events', self::columns($include), $id);
		
		$eventDto = self::writeEventDto($event);

		header('Content-Type: application/json; charset=utf-8');
		header('ETag: ' . $event['version']);
		echo json_encode($eventDto);
		
	}
	
	function getICal() {
		
		if(!isset($_GET['id'])) {
			throw new Exception('"id" is not set');
		}
		$id = $_GET['id'];
		
		$event = $this->db->get('Events', self::columns(), $id);
		
		$iCal = self::writeICalEvents(array($event));

		header('Content-Type: text/calendar; charset=utf-8');
		header('Content-Disposition: attachment; filename="cal.ics"');
		echo $iCal;
		
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
		
		header('Content-Type: application/json; charset=utf-8');
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
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(new stdClass());
		
	}
	
	private static function columns($include = '') {
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
	
	private static function writeICalEvents($events) {
		
		$timeZone = new DateTimeZone('Europe/Berlin');
		
		$earliestStartTime = new DateTimeImmutable('now', $timeZone);
		$latestEndTime = $earliestStartTime;
		
		$iEvents = array();
		foreach($events as $event) {
			
			$startTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $event['startTime'], $timeZone);
			$endTime = $startTime->add(new DateInterval('PT3H'));
			
			if($endTime > $latestEndTime) {
				$latestEndTime = $endTime;
			}
			
			$iEvent = new Eluceo\iCal\Domain\Entity\Event(new Eluceo\iCal\Domain\ValueObject\UniqueIdentifier($event['id']));
			$iEvent->setSummary($event['title'] . (!empty($event['subtitle']) ? ' - ' . $event['subtitle'] : ''));
			$iEvent->setDescription('Dauer variabel');
			//$iEvent->setUrl(new Eluceo\iCal\Domain\ValueObject\Uri("https://example.org/calendarevent"));
			$iEvent->setOccurrence(new Eluceo\iCal\Domain\ValueObject\TimeSpan(new Eluceo\iCal\Domain\ValueObject\DateTime($startTime, true), new Eluceo\iCal\Domain\ValueObject\DateTime($endTime, true)));
			$iEvent->setLocation((new Eluceo\iCal\Domain\ValueObject\Location('Ludwigspl. 5/1, 83022 Rosenheim'))->withGeographicPosition(new Eluceo\iCal\Domain\ValueObject\GeographicPosition(47.8556782, 12.1290316)));

			array_push($iEvents, $iEvent);
			
		}

		$iCalendar = new Eluceo\iCal\Domain\Entity\Calendar($iEvents);
		$iCalendar->addTimeZone(Eluceo\iCal\Domain\Entity\TimeZone::createFromPhpDateTimeZone($timeZone, $earliestStartTime, $latestEndTime));

		$iCalendarFactory = new Eluceo\iCal\Presentation\Factory\CalendarFactory();
		
		return $iCalendarFactory->createCalendar($iCalendar);
		
	}
	
}

?>