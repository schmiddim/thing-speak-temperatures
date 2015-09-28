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


/**
 * found here http://stackoverflow.com/a/11871948
 * @param $input
 * @param $pad_length
 * @param string $pad_string
 * @param int $pad_type
 * @return string
 */
function mb_str_pad($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT) {
	mb_internal_encoding('utf-8'); // @important
	$diff = strlen($input) - mb_strlen($input);
	return str_pad($input, $pad_length + $diff, $pad_string, $pad_type);
}

$client = new Zend\Http\Client();

$maxLenChannelName = 0;
$maxLenChannelValue = 0;

$channelNames = array();
$channelValues = array();
foreach ($thingSpeakChannels as $channelId) {
	$url = sprintf('http://api.thingspeak.com/channels/%d/feed.json', $channelId);
	$client->setUri($url)->setMethod(\Zend\Http\Request::METHOD_GET);
	$response = $client->send();
	$responseObject = \Zend\Json\Json::decode($response->getBody());
	$lastRecord = end($responseObject->feeds);
	$d = new \DateTime($lastRecord->created_at);
	$d->setTimezone(new \DateTimeZone(date_default_timezone_get()));

	if (mb_strlen($responseObject->channel->name) > $maxLenChannelName) {
		$maxLenChannelName = mb_strlen($responseObject->channel->name);
	}
	if (mb_strlen($lastRecord->field1) > $maxLenChannelValue) {
		$maxLenChannelValue = mb_strlen($lastRecord->field1);
	}

	$channelNames[] = $responseObject->channel->name;
	$channelValues[] = $lastRecord->field1;

}

$str = '';
foreach ($channelNames as $index => $channelName) {
	$str .= mb_str_pad($channelName, $maxLenChannelName + 3, ' ', STR_PAD_RIGHT);
	$str .= $channelValues[$index] . ' °C' . PHP_EOL;
}
echo $str;