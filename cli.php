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
			'time|t' => 'show how old  the record is',
			'name|n' => 'show the records name',
			'channels|c=s' => 'Channel ID from ThingSpeak',
			'field|f=d' => 'display only this field',
			'ids|i' => 'display the channel ids'
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
//Channel ID
$channelId = trim($opts->getOption('c'));

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

function getJsonObject($channelId) {
	$client = new Zend\Http\Client();
	$url = sprintf('http://api.thingspeak.com/channels/%d/feed.json', $channelId);
	$client->setUri($url)->setMethod(\Zend\Http\Request::METHOD_GET);
	$response = $client->send();
	$responseObject = \Zend\Json\Json::decode($response->getBody());
	if (-1 === $responseObject) {
		exit(sprintf('Nothing found under %s' . PHP_EOL, $url));
	}
	return $responseObject;
}

/**
 * Pass a nested array and get well formatted output
 * @param array $rows
 * @return string
 */
function generateOutput($rows = array(), $columnsToHide = array()) {
	$maxLengthsColumn = array();
	$str = '';
	//get length of the strings
	foreach ($rows as $rowKey => $row) {
		foreach ($row as $columnKey => $column) {
			if (false === array_key_exists($columnKey, $maxLengthsColumn)) {
				$maxLengthsColumn[$columnKey] = mb_strlen($column);
			}
			if (mb_strlen($column) > $maxLengthsColumn[$columnKey]) {
				$maxLengthsColumn[$columnKey] = mb_strlen($column);
			}
		}
	}
	//write output to string
	foreach ($rows as $rowKey => $row) {
		foreach ($row as $columnKey => $column) {
			if (false == in_array($columnKey, $columnsToHide)) {
				$str .= mb_str_pad($column, $maxLengthsColumn[$columnKey] + 3, ' ', STR_PAD_RIGHT);
			}
		}
		$str .= PHP_EOL;
	}
	return $str;
}

$responseObject = getJsonObject($channelId);

if (true === empty($responseObject->feeds)) {
	exit(sprintf('No entries for %s' . PHP_EOL, $channelId));
}

$lastRecord = end($responseObject->feeds);
//get date of last record
$date = new \DateTime($lastRecord->created_at);
$date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
$now = new \DateTime();
//@see http://php.net/manual/de/dateinterval.format.php
$interval = $now->diff($date);
$dateIntervalsInSeconds = $interval->format('%H:%I:%S');;

$channelName = $responseObject->channel->name;
$numberOfChannels = count((array)$lastRecord) - 2;


$arrayObject = (array)$lastRecord;
$results = array();

for ($i = 1; $i <= $numberOfChannels; $i++) {
	$attributeName = 'field' . $i;
	$resultRow = array();
	$resultRow['field'] = ucfirst($attributeName);
	$resultRow['channelName'] = $responseObject->channel->{$attributeName};
	$resultRow['channeValue'] = $arrayObject[$attributeName];
	$results[$i] = $resultRow;

}

//hide some fields
$columnsToHide = array();

if(null !== $opts->getOption('i')){
	$columnsToHide[] = 'field';
}
if(null !== $opts->getOption('n')){
	$columnsToHide[] = 'channelName';
}

//show only a certain field
$fieldId= $opts->getOption('f');
if(null!==$fieldId) {
	if(false === array_key_exists($fieldId, $results)){
		exit(sprintf('Field with id %s does not exist'. PHP_EOL, $fieldId));
	}
	$results = array($results[$fieldId]);
}

//Output the headline
if (null === $opts->getOption('t')) {
	echo $channelName . ' ' . $dateIntervalsInSeconds . ' ago ' . PHP_EOL;

}
//Output the rest
echo generateOutput($results, $columnsToHide);
