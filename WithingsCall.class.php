<?php
class WithingsCall
{

	// Application parameters
	private $client_id = '';
	private $client_secret = '';
	private $redirect_uri = '';

	// Withings API URLs
	public $auth_url = 'https://account.withings.com/oauth2_user/authorize2';
	public $endpoint_url = 'https://wbsapi.withings.net';

	// Auth Request Parameters
	private $state = '';
	private $access_code = '';

	// User Request Parameters
	private $userid = 0;
	private $access_token = '';
	private $refresh_token = '';

	// Response
	public $response = [];

	public function __construct() 
	{

		// Configuration Timezone
		date_default_timezone_set("Europe/Paris");

		// Debug Mode
		// error_reporting(E_ALL); 
		// ini_set("display_errors", 1); 

		// Send Content-Type
		header('Content-Type: application/json');

		// Init State Parameters
		$this->state = sha1('julienjulien_wants_to_be_in_withings_developers_team');

		// Get App Credentials
		$app_credentials = json_decode(file_get_contents('./app_credentials.json'));

		// Save App Credentials
		$this->client_id = $app_credentials->client_id;
		$this->client_secret = $app_credentials->client_secret;
		$this->redirect_uri = $app_credentials->redirect_uri;

		// Init Success Steps
		$this->response["steps"] = [];
		$this->response["steps"][] = 'Initialisation ✅';

		// Redirect User to Access Page
		if(!isset($_GET["code"]) AND !isset($_GET["state"])) {
			$this->redirectToAuthorizationPage();
		}
		// Get Data 
		else {
			$this->saveAccessCode();
			$this->getAccessToken();
			$this->getMeasureGetmeas();
		}

	}

	// Function to Redirect to Authorization Page
	public function redirectToAuthorizationPage()
	{

		// Redirect parameters
		$response_type = 'code';
		$scope = 'user.metrics';
		
		// Redirect URL
		$redirect_url = $this->auth_url.'?response_type='.$response_type.'&client_id='.$this->client_id.'&state='.$this->state.'&scope='.$scope.'&redirect_uri='.$this->redirect_uri.'&mode=demo';

		// Redirect
		header("Location: ".$redirect_url);
		die();
	}

	// Function to Save Access Code after Redirection
	public function saveAccessCode()
	{
		// Access Code KO
		if($_GET["state"] !== $this->state) {
			
			// Send error
			$this->response["status"] = "error";
			$this->response["error"] = 'Access code ❌';
			http_response_code(500);
			echo json_encode($this->response);
			exit();
		}
		// Access Code OK
		else {

			// Save access code
			$this->access_code = $_GET["code"];

			// Save success step
			$this->response["steps"][] = 'Access code ✅';
		}

		return true;
	}

	// Function to Get User Access Token
	public function getAccessToken() {

		$ch = curl_init();

		// CURL Request Init
		curl_setopt($ch, CURLOPT_URL, $this->endpoint_url."/v2/oauth2");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		// CURL Request Body
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([ 
			'action' => 'requesttoken',
			'grant_type' => 'authorization_code',
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'code' => $this->access_code,
			'redirect_uri' => $this->redirect_uri
		]));

		// CURL Request
		$rsp = curl_exec($ch);
		curl_close($ch);

		$json = json_decode($rsp); // Convert to JSON

		// Access token KO
		if($json->status) {

			// Send error
			$this->response["status"] = "error";
			$this->response["error"] = 'Access token ❌';
			http_response_code(500);
			echo json_encode($this->response);
			exit();
			
		}
		// Access token OK
		else {

			$body = $json->body;

			// Save response
			$this->userid = $body->userid;
			$this->access_token = $body->access_token;
			$this->refresh_token = $body->refresh_token;

			// Save success step
			$this->response["steps"][] = 'Access Token ✅';

		}
		
		return true;
	}

	// Function to Get last measure
	public function getMeasureGetmeas() {

		// Type of measure : Weight
		$meatype = 1; 

		$ch = curl_init();

		// CURL Request Init
		curl_setopt($ch, CURLOPT_URL, $this->endpoint_url."/measure");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Authorization: Bearer '.$this->access_token
		]);

		// CURL Request Body
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([ 
			'action' => 'getmeas',
			'meastype' => $meatype,
		]));

		// CURL Request
		$rsp = curl_exec($ch);
		curl_close($ch);

		$json = json_decode($rsp); // Convert to JSON

		// Error while getting Access Token
		if($json->status) {

			// Send error
			$this->response["status"] = "error";
			$this->response["error"] = 'Get data ❌';
			http_response_code(500);
			echo json_encode($this->response);
			exit();
			
		}
		// Access Token OK
		else {

			$body = $json->body;

			// Get last weight measure
			$result = $body->measuregrps[0];

			// Save success step
			$this->response["steps"][] = 'Get data ✅';

			// Send response
			$this->response["status"] = "success";
			$this->response["result"] = $result;
			http_response_code(200);
			echo json_encode($this->response);
			exit();
			
		}		

	}

}
?>
