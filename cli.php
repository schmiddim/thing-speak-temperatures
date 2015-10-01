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
            'field|f=d' => 'display only this field'
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
function mb_str_pad($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT)
{
    mb_internal_encoding('utf-8'); // @important
    $diff = strlen($input) - mb_strlen($input);

    return str_pad($input, $pad_length + $diff, $pad_string, $pad_type);
}

$client = new Zend\Http\Client();

$maxLenChannelName = 0;
$maxLenChannelValue = 0;


if (false === empty($channelId)) {
    $url = sprintf('http://api.thingspeak.com/channels/%d/feed.json', $channelId);
    $client->setUri($url)->setMethod(\Zend\Http\Request::METHOD_GET);
    $response = $client->send();

    $responseObject = \Zend\Json\Json::decode($response->getBody());
    $responseAsArray = (array)$responseObject;
    $lastRecord = end($responseObject->feeds);

    //get date of last record
    $date = new \DateTime($lastRecord->created_at);
    $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    $now = new \DateTime();
    //@see http://php.net/manual/de/dateinterval.format.php
    $interval = $now->diff($date);
    $dateIntervalsInSeconds = $interval->format('%H:%I:%s');;

    $channelName = $responseObject->channel->name;
    $numberOfChannels = count((array)$lastRecord) - 2;


    $arrayObject = (array)$lastRecord;
    if (mb_strlen($responseObject->channel->name) > $maxLenChannelName) {
        $maxLenChannelName = mb_strlen($responseObject->channel->name);
    }
    if (mb_strlen($lastRecord->field1) > $maxLenChannelValue) {
        $maxLenChannelValue = mb_strlen($lastRecord->field1);
    }
}


//output
$str = '';
if (null !== $opts->getOption('t')) {
    $str .= $channelName . ' ' . $dateIntervalsInSeconds . ' ago ' . PHP_EOL;

}

if (null === $opts->getOption('f')) {

    for ($i = 1; $i <= $numberOfChannels; $i++) {

        $attributeName = 'field' . $i;

        if (null !== $opts->getOption('n')) {
            $str .= mb_str_pad($responseObject->channel->{$attributeName}, $maxLenChannelName + 3, ' ', STR_PAD_RIGHT);
        }
        $str .= $arrayObject[$attributeName];
        $str .= PHP_EOL;

    }
} else {
    $i = $opts->getOption('f');

    if (array_key_exists('field'.$i, $arrayObject)) {

        $attributeName = 'field' . $i;

        if (null !== $opts->getOption('n')) {
            $str .= mb_str_pad($responseObject->channel->{$attributeName}, $maxLenChannelName + 3, ' ', STR_PAD_RIGHT);
        }
        $str .= $arrayObject[$attributeName];
        $str .= PHP_EOL;
    }else {
        $str.=sprintf('Field %s is not set!' . PHP_EOL, $i);
    }
}

echo $str;