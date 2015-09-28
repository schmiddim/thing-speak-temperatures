<?php
/**
 * User: ms
 * Date: 29.09.15
 * Time: 01:04
 */

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

//wohnzimmer 58117
//büro 58114
$thingSpeakChannels = array(58117, 58114);

$client = new Zend\Http\Client();


foreach ($thingSpeakChannels as $channelId) {
	$url = sprintf('http://api.thingspeak.com/channels/%d/feed.json', $channelId);
	$client->setUri($url)->setMethod(\Zend\Http\Request::METHOD_GET);
	$response = $client->send();
	$responseObject = \Zend\Json\Json::decode($response->getBody());
	$lastRecord = end($responseObject->feeds);
	$d = new \DateTime($lastRecord->created_at);
	$d->setTimezone(new \DateTimeZone(date_default_timezone_get()));
	echo $responseObject->channel->name . ' ' . $d->format('H:m:s') . ' ' . $lastRecord->field1 . ' °C' . PHP_EOL;


}
