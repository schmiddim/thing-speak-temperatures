Temperature in my rooms.
from thingspeak

Usage:

Usage: cli.php [ options ]
--time|-t              show how old  the record is
--name|-n              show the records name
--channels|-c <string> Channel ID from ThingSpeak
--field|-f <string>    display only this field


Example:
php cli.php --channels=58114 -nt -f 1
