<?php

class EventsGateway {
	
	private $schema;
	
	private $db;
	
	private $dbTypes;
	
	function __construct($db) {
		$this->schema = json_decode(file_get_contents(dirname(__FILE__) . '/EventsSchema.json'), FALSE);
		$this->db = $db;
		$this->dbTypes = 'sissssssssssssssss';
    }
	
	function list() {

		if(!isset($_GET['upcoming'])) {
			$upcoming = null;
		} else if(!is_numeric($_GET['upcoming'])) {
			$upcoming = -1;
		} else {
			$upcoming = intval($_GET['upcoming']);
		}

		if(!isset($_GET['upcomingDays']) || !is_numeric($_GET['upcomingDays'])) {
			$upcomingDays = null;
		} else {
			$upcomingDays = intval($_GET['upcomingDays']);
		}

		if(!isset($_GET['year']) || !is_numeric($_GET['year'])) {
			$year = null;
		} else {
			$year = intval($_GET['year']);
		}

		if(!isset($_GET['month']) || !is_numeric($_GET['month'])) {
			$month = null;
		} else {
			$month = intval($_GET['month']);
		}
		
		if($upcoming === null && $upcomingDays === null && $year === null) {
			throw new Exception('No filter present');
		}

		if(!isset($_GET['include'])) {
			$include = '';
		} else {
			$include = $_GET['include'];
		}
		
		$sortDirection = isset($_GET['descending']) ? 'DESC' : 'ASC';

		if($upcoming !== null) {
			$events = $this->db->list('Events', self::columns($include), '`startTime` > NOW()', '', array(), 'startTime', $sortDirection, $upcoming >= 0 ? $upcoming : null);
		} else if($upcomingDays !== null) {
			$events = $this->db->list('Events', self::columns($include), '`startTime` > NOW() AND `startTime` <= NOW() + INTERVAL ? DAY', 'i', array($upcomingDays), 'startTime', $sortDirection, null);
		} else if($month === null) {
			$events = $this->db->list('Events', self::columns($include), 'YEAR(`startTime`) = ?', 'i', array($year), 'startTime', $sortDirection, null);
		} else {
			$events = $this->db->list('Events', self::columns($include), 'YEAR(`startTime`) = ? AND MONTH(`startTime`) = ?', 'ii', array($year, $month), 'startTime', $sortDirection, null);
		}
		
		$eventDtos = array_map('self::writeEventDto', $events);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($eventDtos);
		
	}
	
	function listComingICal() {
		
		$events = $this->db->list('Events', self::columns(), '`startTime` > NOW()', '', array(), 'startTime', 'ASC', null);
		
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
	
	function getImage2() {
		
		if(!isset($_GET['id'])) {
			throw new Exception('"id" is not set');
		}
		$id = $_GET['id'];
		
		if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && intval($_SERVER['HTTP_IF_NONE_MATCH']) === $this->db->getVersion('Events', $id)) {
			http_response_code(304);
			exit;
		}

		$event = $this->db->get('Events', array('version', 'image2'), $id);
		
		if($event['image2'] === null) {
			throw new Exception('No image2 set');
		}

		$eventImageParts = preg_split('/:|;|,/', $event['image2']);
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
			throw new Exception($errorMessage);
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
		$columns = array('id', 'version', 'organizer', 'status', 'startTime', 'title', 'subtitle', 'series', 'color', 'warning');
		if(in_array('content', $includes, TRUE)) {
			$columns = array_merge($columns, array('text', 'lineup', 'price', 'entry', 'notes'));
		}
		if(in_array('image', $includes, TRUE)) {
			$columns = array_merge($columns, array('image'));
		}
		if(in_array('image2', $includes, TRUE)) {
			$columns = array_merge($columns, array('image2'));
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
		$event['organizer'] = $eventDto->organizer;
		$event['status']    = $eventDto->status;
		$event['startTime'] = substr($eventDto->startTime, 0, 10) . ' ' . substr($eventDto->startTime, 11, 5) . ':00';
		$event['title']     = $eventDto->title;
		$event['subtitle']  = $eventDto->subtitle;
		$event['series']    = $eventDto->series;
		$event['color']     = $eventDto->color;
		$event['warning']   = $eventDto->warning;
		$event['text']      = $eventDto->text;
		$event['lineup']    = $eventDto->lineup;
		$event['price']     = $eventDto->price;
		$event['entry']     = $eventDto->entry === null ? null : $eventDto->entry . ':00';
		$event['notes']     = $eventDto->notes;
		$event['image']     = $eventDto->image;
		$event['image2']    = $eventDto->image2;
		$event['links']     = json_encode($eventDto->links);
		
		return $event;
		
	}
	
	private static function writeEventDto($event) {
		
		$eventDto = array();
		$eventDto['id']        = $event['id'];
		$eventDto['version']   = $event['version'];
		$eventDto['organizer'] = $event['organizer'];
		$eventDto['status']    = $event['status'];
		$eventDto['startTime'] = substr($event['startTime'], 0, 10) . 'T' . substr($event['startTime'], 11, 5);
		$eventDto['title']     = $event['title'];
		$eventDto['subtitle']  = $event['subtitle'];
		$eventDto['series']    = $event['series'];
		$eventDto['color']     = $event['color'];
		$eventDto['warning']   = $event['warning'];
		
		if(array_key_exists('text', $event)) {
			$eventDto['text']   = $event['text'];
			$eventDto['lineup'] = $event['lineup'];
			$eventDto['price']  = $event['price'];
			$eventDto['entry']  = substr($event['entry'], 0, 5);
			$eventDto['notes']  = $event['notes'];
		}
		
		if(array_key_exists('image', $event)) {
			$eventDto['image'] = $event['image'];
		}
		
		if(array_key_exists('image2', $event)) {
			$eventDto['image2'] = $event['image2'];
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
			$iEvent->setDescription('(Die Dauer dieser Veranstaltung ist pauschal mit 3 Stunden angegeben. Die tatsächliche Dauer weicht davon ab!)');
			$iEvent->setOccurrence(new Eluceo\iCal\Domain\ValueObject\TimeSpan(new Eluceo\iCal\Domain\ValueObject\DateTime($startTime, true), new Eluceo\iCal\Domain\ValueObject\DateTime($endTime, true)));
			$iEvent->setLocation((new Eluceo\iCal\Domain\ValueObject\Location('Ludwigspl. 5/1, 83022 Rosenheim')));

			array_push($iEvents, $iEvent);
			
		}

		$iCalendar = new Eluceo\iCal\Domain\Entity\Calendar($iEvents);
		$iCalendar->addTimeZone(Eluceo\iCal\Domain\Entity\TimeZone::createFromPhpDateTimeZone($timeZone, $earliestStartTime, $latestEndTime));

		$iCalendarFactory = new Eluceo\iCal\Presentation\Factory\CalendarFactory();
		
		return $iCalendarFactory->createCalendar($iCalendar);
		
	}
	
}

?>