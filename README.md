Temperature in my rooms.
from thingspeak

Usage:

cli.php [ options ]
--time|-t              hide how old  the record is
--name|-n              hide the records name
--channels|-c <string> Channel ID from ThingSpeak
--field|-f <string>    display only this field
--ids|-i               display the channel ids

Example:
php cli.php --channels=58114
