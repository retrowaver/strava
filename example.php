<?php

require_once(__DIR__.'/class-strava.php');

$strava = new Strava;
$strava->login('email@email.com','password');

print_r($strava->getRouteInfo(50,20,50.05,20.05,1,0,1));


?>