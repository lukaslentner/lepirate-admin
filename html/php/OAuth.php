<?php

class OAuth {

	private $accessToken;
	
	private $userRoles;

	function __construct() {

		session_start();
		
		$url = _HOST . $_SERVER['REQUEST_URI'];
		
		if(isset($_SESSION['oAuth'])) {
			
			if($_SESSION['oAuth-expires'] <= strtotime('now')) {
				unset($_SESSION['oAuth']);
				session_destroy();
				header('Location: ' . $_SERVER['REQUEST_URI']);
				exit;
			}
			
			if(isset($_GET['_oAuth-action']) && $_GET['_oAuth-action'] == 'logout') {
				unset($_SESSION['oAuth']);
				session_destroy();
				header('Location: ' . _OAUTH_LOGOUT);
				exit;
			}

			if($_SESSION['oAuth-expires'] < strtotime('+10 minutes')) {
				
				//attempt token refresh
				if(isset($_SESSION['oAuth-refreshToken'])) {
					
					$request = 'grant_type=refresh_token&refresh_token=' . $_SESSION['oAuth-refreshToken'] . '&client_id=' . _OAUTH_CLIENTID . '&client_secret=' . urlencode(_OAUTH_SECRET) . '&scope=' . _OAUTH_SCOPE;
					$ch = curl_init(_OAUTH_SERVER . 'token');
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$rawResponse = curl_exec($ch);
					if ($error = curl_error($ch)) {
						die($error);
					}
					curl_close($ch);
					$response = json_decode($rawResponse);
					if(property_exists($response, 'error')) {
						
						//refresh token expired
						if(substr($response->error_description, 0, 12) == 'AADSTS70008:') {
							
							$_SESSION['oAuth-refreshToken'] = '';
							$_SESSION['oAuth-redirect'] = $url;
							$_SESSION['oAuth-expires'] = strtotime('+5 minutes');
							
							$challenge = $this->buildChallenge($_SESSION['oAuth-verifier']);
							$request = _OAUTH_SERVER . 'authorize?response_type=code&client_id=' . _OAUTH_CLIENTID . '&redirect_uri=' . urlencode(_URL . 'oauth.php') . '&scope=' . _OAUTH_SCOPE . '&code_challenge=' . $challenge[0] . '&code_challenge_method=' . $challenge[1] . '&resource=' . urlencode('https://graph.microsoft.com/');
							
							header('Location: ' . $oAuthURL);
							exit;
						
						}

						die($response->error_description);
						
					}
					
					$_SESSION['oAuth-accessToken'] = $response->access_token;
					$_SESSION['oAuth-refreshToken'] = $response->refresh_token;
					$_SESSION['oAuth-redirect'] = '';
					$_SESSION['oAuth-expires'] = strtotime('+' . $response->expires_in . ' seconds');
					
				}
				
			}
			
			$this->accessToken = $_SESSION['oAuth-accessToken'];
			if(isset($_SESSION['oAuth-idToken'])) {
				$idToken = json_decode($_SESSION['oAuth-idToken']);
				$this->userRoles = isset($idToken->roles) ? $idToken->roles : array();
			}
			
		} else {
			
			$verifier = $this->buildVerifier();
			
			$_SESSION['oAuth'] = TRUE;
			$_SESSION['oAuth-redirect'] = $url;
			$_SESSION['oAuth-verifier'] = $verifier;
			$_SESSION['oAuth-expires'] = strtotime('+5 minutes');
			
			$challenge = $this->buildChallenge($verifier);
			$request = _OAUTH_SERVER . 'authorize?response_type=code&client_id=' . _OAUTH_CLIENTID . '&redirect_uri=' . urlencode(_URL . 'oauth.php') . '&scope=' . _OAUTH_SCOPE . '&code_challenge=' . $challenge[0] . '&code_challenge_method=' . $challenge[1] . '&resource=' . urlencode('https://graph.microsoft.com/');
			
			header('Location: ' . $request);
			exit;
			
		}
		
	}

	private function buildVerifier() {
		$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-._~';
		$charLen = strlen($chars) - 1;
		$verifier = '';
		for ($i = 0; $i < 128; $i++) {
			$verifier .= $chars[mt_rand(0, $charLen)];
		}
		return $verifier;
	}

	private function buildChallenge($verifier) {
		return array(str_replace('=', '', strtr(base64_encode(pack('H*', hash('sha256', $verifier))), '+/', '-_')), 'S256');
	}
	
	public function getAccessToken() {
		return $this->accessToken;
	}
	
	public function getUserRoles() {
		return $this->userRoles;
	}
	
	public static function handleCallback() {
		
		session_start();

		if($_GET['error']) {
			die($_GET['error_description']);
		}
		
		if(isset($_SESSION['oAuth'])) {
			
			$request = 'grant_type=authorization_code&client_id=' . _OAUTH_CLIENTID . '&redirect_uri=' . urlencode(_URL . 'oauth.php') . '&code=' . $_GET['code'] . '&client_secret=' . urlencode(_OAUTH_SECRET) . '&code_verifier=' . $_SESSION['oAuth-verifier'] . '&resource=' . urlencode('https://graph.microsoft.com/');
			$ch = curl_init(_OAUTH_SERVER . 'token');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$rawResponse = curl_exec($ch);
			if ($error = curl_error($ch)) {
				die($error);
			}
			curl_close($ch);
			$response = json_decode($rawResponse);
			if($response->error) {
				die($response->error_description);
			}
			
			$idToken = base64_decode(explode('.', $response->id_token)[1]);
			$redirect = $_SESSION['oAuth-redirect'];
			
			$_SESSION['oAuth-accessToken'] = $response->access_token;
			$_SESSION['oAuth-refreshToken'] = $response->refresh_token;
			$_SESSION['oAuth-idToken'] = $idToken;
			$_SESSION['oAuth-redirect'] = '';
			$_SESSION['oAuth-expires'] = strtotime('+' . $response->expires_in . ' seconds');
			
		}

		header('Location: ' . $redirect);
		
	}
	
}

?>