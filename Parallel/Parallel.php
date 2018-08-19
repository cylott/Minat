<?

// Parallel Runner 0.1.0
// Execute bash script with GNU parallel and monitor results

$lines = count(file($argv[1]));
$glob_string = dirname($argv[1])."/*.lock";
if ($argv[3]) { $log = $argv[3]; } else { $log = "/dev/null"; }

echo "Starting ".$lines." threads...";

exec(__DIR__."/parallel < ".escapeshellarg($argv[1])." >> ".$log." 2>&1 &");

echo "\nPROGRESS:0\n";

while (count(glob($glob_string)) < $lines) {
	
	if (count(glob($glob_string))) {
	
		// Terrible hack to detect files added to the destination and update the status with the last completed filename
	
		$inotify_hack = exec("find ".dirname($argv[1])."/*.lock -type f -print0 | xargs -0 stat -f \"%m %N\" | sort -rn | head -1 | cut -f2- -d\" \"");
		echo substr(basename($inotify_hack,".lock"),33);
		}
	
	echo "\nPROGRESS:".floor((count(glob($glob_string))/$lines)*100)."\n";
	usleep(10000);

	}

echo "\nPROGRESS:100\n";

// Postflight script

if ($argv[2]) {
	exec("/bin/bash ".$argv[2]);
	}

if ($argv[4]) {
	exec("afplay -v .5 ".__DIR__."/ding.mp3");
	}

?>