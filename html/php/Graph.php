<?php

class Graph {

	private $oAuth;

	function __construct($oAuth) {
		$this->oAuth = $oAuth;
	}
	
	function getProfile() {
		return json_decode($this->sendGetRequest('https://graph.microsoft.com/v1.0/me/'));
	}
	
	function getPhoto() {
		$photoType = json_decode($this->sendGetRequest('https://graph.microsoft.com/v1.0/me/photo/'));
		if($photoType == null) {
			return null;
		}
		$photo = $this->sendGetRequest('https://graph.microsoft.com/v1.0/me/photo/%24value');
		return 'data:' . $photoType->{'@odata.mediaContentType'} . ';base64,' . base64_encode($photo);
	}
	
	private function sendGetRequest($url, $contentType = 'application/json') {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->oAuth->getAccessToken(), 'Content-Type: ' . $contentType));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$rawResponse = curl_exec($ch);
		if($error = curl_error($ch)) {
			die($error);
		}
		curl_close($ch);
		$response = json_decode($rawResponse);
		if(isset($response->error)) {
			
			if($response->error->code == 'ImageNotFound') {
				return 'null';
			}
			
			die($rawResponse);
		
		}
		return $rawResponse;
	}
}

?>