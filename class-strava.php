<?php

class Strava {
	
	public function __construct() {
		
		$this->curl = curl_init();
		
		curl_setopt($this->curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, realpath(__DIR__."/cookie.txt"));
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, realpath(__DIR__."/cookie.txt"));
		curl_setopt($this->curl, CURLOPT_COOKIESESSION, true);
		curl_setopt($this->curl, CURLOPT_HEADER, 1);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, 20);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
		
	}
	
	public function login($email,$password) {
		
		//Downloading login page
		
		curl_setopt($this->curl, CURLOPT_URL, 'https://www.strava.com/login');
		curl_setopt($this->curl, CURLOPT_POST, 0);
		
		$result = curl_exec($this->curl);
	
		//Parsing authenticity token from the login page
	
		preg_match('/authenticity\_token\"\ type\=\"hidden\"\ value\=\"(.*?)\"/',$result,$matches);
		
		if(empty($matches[1])) {
			
			throw new Exception('Couldn\'t log into Strava.');
			
		}
		
		//Preparing postfields
		
		$postfields = array();
		
		$postfields['utf8'] = urldecode('%E2%9C%93');
		$postfields['authenticity_token'] = $matches[1];
		$postfields['email'] = $email;
		$postfields['password'] = $password;
		
		//Posting fields

		curl_setopt($this->curl, CURLOPT_URL, 'https://www.strava.com/session');
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postfields);
		
		if(!curl_exec($this->curl)) {
			
			throw new Exception('Curl error (while logging in).');
			
		}
		
		return true;
		
	}
	
	public function getRouteInfo($latitudeX,$longitudeX,$latitudeY,$longitudeY,$usePopularity,$minElevation,$routeType) {
		
		/*
		$latitudeX - latitude of the start point
		$longitudeX - longitude of the start point
		
		$latitudeY - latitude of the finish point
		$longitudeY - longitude of the finish point
		
		$usePopularity - 0 or 1 - Strava feature to prefer popular paths during creating a route
		$minElevation - 0 or 1 - Strava feature to make the route as flat as possible
		$routeType - 1 for cycling, 2 for running
		*/
		
		//Downloading page of route creator
		
		curl_setopt($this->curl, CURLOPT_URL, 'https://www.strava.com/routes/new');
		curl_setopt($this->curl, CURLOPT_POST, 0);
		
		$result = curl_exec($this->curl);
		
		//Parsing csrf token
		
		preg_match('/\"(.*?)\"\ name\=\"csrf\-token\"/',$result,$matches);
		
		//Preparing JSON object
		
		$json = new stdClass();
		
		$json->elements = array();
		$json->elements[0] = new stdClass();
		$json->elements[0]->element_type = 1;
		$json->elements[0]->waypoint = new stdClass();
		$json->elements[0]->waypoint->point = new stdClass();
		$json->elements[0]->waypoint->point->lat = $latitudeX;
		$json->elements[0]->waypoint->point->lng = $longitudeX;
		
		$json->elements[1] = new stdClass();
		$json->elements[1]->element_type = 1;
		$json->elements[1]->waypoint = new stdClass();
		$json->elements[1]->waypoint->point = new stdClass();
		$json->elements[1]->waypoint->point->lat = $latitudeY;
		$json->elements[1]->waypoint->point->lng = $longitudeY;
		
		$json->preferences = new stdClass();
		$json->preferences->popularity = $usePopularity;
		$json->preferences->elevation = $minElevation;
		$json->preferences->route_type = $routeType;
		$json->preferences->straight_line = false;
		
		$json = json_encode($json);
		
		//Sending JSON object
		
		curl_setopt($this->curl, CURLOPT_URL, 'https://www.strava.com/routemaster/route');
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_HEADER, 0);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $json);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
			"X-CSRF-Token: ".$matches[1],
			"X-Requested-With: XMLHttpRequest",
			"Content-Type: application/json; charset=utf-8",
		));
		curl_setopt($this->curl,CURLOPT_REFERER, 'https://www.strava.com/routes/new');
		
		if(!$result = curl_exec($this->curl)) {
			
			throw new Exception('Curl error (while fetching route data).');
			
		}
		
		if(!$result = json_decode($result)) {
			
			throw new Exception('Curl error (while fetching route data - no json object returned).');
			
		}
		
		return $result;
		
	}
	
}

?>