<?php
/**
 * User: ms
 * Date: 29.09.15
 * Time: 01:04
 */

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

try {
	$opts = new Zend\Console\Getopt(
		array(
			'time|t' => 'show how old is the value',
			'name|n' => 'show the name of the value',
			'channels|c=s' => 'Departure Stations - separate multiple stations with ;',
		)
	);
	$opts->parse();

} catch (Zend\Console\Exception\RuntimeException $e) {
	echo $e->getUsageMessage();
	exit;
}
if (null === $opts->getOption('c')) {
	echo $opts->getUsageMessage();
	exit;
}

//Stations
$channelString = trim($opts->getOption('c'));
$channels = explode(';', $channelString);


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
$maxLenDateIntervalInSeconds = 0;

$channelNames = array();
$channelValues = array();
$dateValues = array();
$dateIntervalsInSeconds = array();
foreach ($channels as $channelId) {
	if (false === empty($channelId)) {
		$url = sprintf('http://api.thingspeak.com/channels/%d/feed.json', $channelId);
		$client->setUri($url)->setMethod(\Zend\Http\Request::METHOD_GET);
		$response = $client->send();

		$responseObject = \Zend\Json\Json::decode($response->getBody());
		$responseAsArray = (array)$responseObject;
		$lastRecord = end($responseObject->feeds);

		$date = new \DateTime($lastRecord->created_at);
		$date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
		$now = new \DateTime();
		//@see http://php.net/manual/de/dateinterval.format.php
		$interval = $now->diff($date);

		//calculate the absolute interval
		$hours =$interval->h;
		$timeString='';
		if($interval->h >0) {
			$timeString.=$interval->h .':';

		}
		if($interval->i >0 || $interval->h >0) {
			$timeString.=$interval->i. ':';

		}
		if($interval->s >0) {
			$timeString.=$interval->s;

		}
		$dateIntervalsInSeconds[] = $timeString;


		$dateValues[] = $date->format('Y-m-d H:i:s');

		$channelNames[] = $responseObject->channel->name;
		$channelValues[] = $lastRecord->field1;

		$numberOfChannels = count((array)$lastRecord) - 2;
		$arrayObject = (array)$lastRecord;
		if (mb_strlen($responseObject->channel->name) > $maxLenChannelName) {
			$maxLenChannelName = mb_strlen($responseObject->channel->name);
		}
		if (mb_strlen($lastRecord->field1) > $maxLenChannelValue) {
			$maxLenChannelValue = mb_strlen($lastRecord->field1);
		}
		if (mb_strlen($interval->s) > $maxLenDateIntervalInSeconds) {
			$maxLenDateIntervalInSeconds = mb_strlen($interval->s);
		}


	}
}

$str = '';
foreach ($channelNames as $index => $channelName) {
	if (true === $opts->getOption('n')) {
		$str .= $channelName .' ';
	}
	if (true === $opts->getOption('t')) {
		$str .= mb_str_pad($dateIntervalsInSeconds[$index] . 's ago ', $maxLenDateIntervalInSeconds + 3, ' ', STR_PAD_RIGHT);
		$str.="\n";
	}

}
echo $str;
#	$str .= $channelValues[$index] . ' °C' . PHP_EOL;
for ($i = 1; $i <= $numberOfChannels; $i++) {
	$attributeName = 'field' . $i;
	echo $responseObject->channel->{$attributeName};
	echo ' ' . $arrayObject[$attributeName] . "\n";

}