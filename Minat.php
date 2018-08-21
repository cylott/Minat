<?php

// Minat
// Minat Is Not a Transcoder

$version = file_get_contents(__DIR__."/version.txt");

// Includes

require (__DIR__."/functions.php");

// Check for translocation

if (!@touch(__DIR__."/test")) {
	alert("Minat cannot run from the Downloads folder");
	quitme();
	die;
	}

// Prefs

$prefs = __DIR__."/prefs.php";
if (!file_exists($prefs)) {
	
	alert("Can't read prefs file");
	die;
	
	} else {
	
	$p = unserialize(file_get_contents($prefs));
	
	$p['phpbin'] = "/usr/bin/php";
	$p['flacbin'] = __DIR__."/bin/flac";
	$p['metaflacbin'] = __DIR__."/bin/metaflac";
	$p['lamebin'] = __DIR__."/bin/lame";
	$p['soxbin'] = __DIR__."/bin/sox";
	
	$p['workdir'] = "/tmp/minat/";
	$p['logfile'] = getLogPath("minat.log");
	$p['disable_prefs'] = 0;
	$p['disable_tags'] = 0;
	$p['disable_artwork'] = 0;
	$p['premature'] = 0;
	$p['max_size'] = 1000;
	
	}

// Version check

if (strpos(__FILE__,".app")) {

	$checkfile = __DIR__."/vcheck";
	
	if (!file_exists($checkfile) | time()-@filemtime($checkfile) > 86400) {
		$curr_version = file_get_contents("https://raw.githubusercontent.com/cylott/Minat/master/version.txt");
		addline("Version check, me=".$version." latest=".$curr_version);
		if ($curr_version > $version) {
			if(askMulti("A new version of Minat is available", array("Skip","Download")) == 1) {
				exec("open https://github.com/cylott/Minat");
				quitme();
				}
			}
		touch($checkfile);
		}
	}

if ($p['mode'] != 1) { $p['premature'] = 1; addline("MODE ".$p['mode']." NOT YET IMPLEMENTED."); }

// If SHIFT key is held down, open debug window

if(exec(__DIR__."/bin/keys") == 512) {
	exec("open -n Console.app --args ".$p['logfile']);
	}

// Make work dir

if (!is_dir($p['workdir'])) {
	addline("mkdir ".$p['workdir']);
	mkdir($p['workdir']);
	}

// Debug info

addline("---------------------------------------");
addline("Init ".time());
addline(var_export($argv, true));
addline("---------------------------------------");
addline(var_export($p, true));
addline("---------------------------------------");
addline("Minat: ".$version);
addline("PHP: ".PHP_VERSION);
addline(exec("sw_vers | grep ProductVersion"));

// Launch without argv (no files dragged)

array_shift($argv);

if(count($argv) == 0) {
	if (!$p['disable_prefs'] && strpos(__FILE__,".app")) {
		exec($p['phpbin']." ".__DIR__."/MinatPrefs.php");
		}
	addline("Launch without argv");
	die;
	}

$stamp = md5(serialize($argv))."_".time();
$workdir = $p['workdir'].$stamp."/";
$batchfile = $workdir.$stamp.".sh";
$postfile = $workdir.$stamp.".post.sh";

addline("---------------------------------------");

// Loop over dragged directories

foreach ($argv as $target) {

	// If a single .flac file is dragged, treat as if its parent dir was dragged
	//if (strtolower(pathinfo($target, PATHINFO_EXTENSION)) == "flac") {
	//	$target = dirname($target);
	//	}

	$files = array();

	foreach(new DirectoryIterator($target) as $file) {
		if (strtolower(@array_pop(explode('.', $file))) == "flac") {
			$files[] = $file->getpathname();
			}
		}

	if (!$files) {
		addline("Dropped folder ".$target." does not contain any flac files");
		alert("Dropped folder ".$target." does not contain any flac files");
		continue;
		}
		
	$label = basename($target);
	if (stripos($label,"[flac]") == false) {
		$newlabel = $label." [MP3]";
		} else {
		$newlabel = str_ireplace("[flac]","[MP3]",$label);
		}
	
	switch ($p['dest']) {
		
		case 0:
		$destdir = $p['workdir'].$stamp."/".$newlabel."/";
		break;
			
		case 1:
		$destdir = dirname($target)."/".$newlabel."/";
		break;

		case 2:
		$destdir = $p['destpath']."/".$newlabel."/";
		break;
			
		}
	
	addline("Dest dir is ".$destdir);
	
	$postdirs[] = $destdir;
		
	if (file_exists($destdir)) {
		alert("Destination directory ".$destdir." already exists!");
		die;
		}
		
	if (!is_dir($workdir)) {
		addline("mkdir ".$workdir);
		mkdir($workdir);
		}
			
	if (!is_dir($destdir) & !$p['premature']) {
		addline("mkdir ".$destdir);
		mkdir($destdir);
		}
	
	if (!$p['disable_artwork']) {
		
		$mimecmd = $p['metaflacbin']." --list --block-type=PICTURE ".escapeshellarg($files[0])." | head -10 | grep MIME | sed 's:.*/::'";
		addline($mimecmd);
		$mime = exec($mimecmd);
		
		if (@$mime) {
			
			addline("embedded cover found in ".$files[0]);
			
			$coverdest = $workdir."cover.".$mime;
			exec($p['metaflacbin']." --export-picture-to=".escapeshellarg($coverdest)." ".escapeshellarg($files[0]));
			if (file_exists($coverdest)) { $usecover = $coverdest; } else { addline ("error extracting file"); }
		
			} elseif ($extcover = findCover($target)) {
			
			addline("external cover file found: ".$extcover);
			
			$width = exec("sips -g pixelWidth ".escapeshellarg($extcover)." | tail -n1 | cut -f4 -d\" \"");
			$height = exec("sips -g pixelHeight ".escapeshellarg($extcover)." | tail -n1 | cut -f4 -d\" \"");
			
			if ($width > $p['max_size'] | $height > $p['max_size']) {
			
				addline("resizing ".$extcover);
				
				$coverdest = $workdir."cover.".pathinfo($extcover, PATHINFO_EXTENSION);				
				exec("sips --resampleHeightWidthMax ".$p['max_size']." ".escapeshellarg($extcover)." --out ".$coverdest." > /dev/null 2>&1");
				if (file_exists($coverdest)) { $usecover = $coverdest; } else { addline ("error resizing file"); }
				
				} else {
				
				$usecover = $extcover;
				
				}
			}
		
		if ($usecover) {
			$covertags = "--ti ".escapeshellarg($usecover);
			} elseif ($p['warn_art']) {
			alert("No cover artwork was found");
			}
		}
	
	// Handle one directory
	
	foreach ($files as $file) {

		// MODE: Transcode

		addline("Processing ".$file);

		if ($p['check']) {
			if (exec($p['flacbin']." -ts ".escapeshellarg($file)." 2>&1")) {
				addline("Skipping corrupted flac: ".$file);
				continue;
				}
			}
		
		$tagcmd = $p['soxbin']." --i -a ".escapeshellarg($file);

		if (!$p['disable_tags']) {
			
			$rawtags = shell_exec($tagcmd);
			addline(var_export($rawtags, true));
			$tags = lameFlagBuilder($rawtags);

			}
		
		$dest = $destdir.basename($file,".flac").".mp3";
		$lockfile = $workdir.md5($target).".".basename($file,".flac").".lock";
		$cmd_flac = $p['flacbin']." -dcs -- ".escapeshellarg($file);
		$cmd_lame = $p['lamebin']." -S ".$p['lameopts']." ".$tags." ".$covertags." - ".escapeshellarg($dest);
		$cmd_lock = "touch ".escapeshellarg($lockfile);
			
		$line[] = $cmd_flac." | ".$cmd_lame." ; ".$cmd_lock;
		
		}
		
	}

// No files were found -- nothing to do

if (!@count($line) | $p['premature']) {
	addline("No files were found in any dropped folder");
	die;
	}

// Write batch file for Parallel
	
addline("Writing to batchfile ".$batchfile);
file_put_contents($batchfile,implode("\n", $line));

$pass[] = escapeshellarg($batchfile);

// Write postflight batch file for Parallel

if ($p['postflight']) {

	foreach ($postdirs as $dir) {
	
		switch ($p['postflight']) {

			case 1:
			$postline[] = "qlmanage -p ".escapeshellarg($dir)."/* > /dev/null 2>&1";
			break;
		
			case 2:
			$postline[] = "open ".escapeshellarg($dir);
			break;
		
			case 3:
			$postline[] = "open ".escapeshellarg($dir)." -a ".escapeshellarg($p['handler']);
			break;
		
			}
		
		}
			
	addline("Writing to postfile ".$postfile);
	file_put_contents($postfile,implode("\n", $postline));
	$pass[] = escapeshellarg($postfile);

	} else {
	
	$pass[] = 0;
	
	}
	
$pass[] = escapeshellarg($p['logfile']);
	
if ($p['ding']) {
	$pass[] = 1;
	}
	
$cmd = "open -n ".__DIR__."/Parallel.app --args ".implode(" ",$pass);
	
addline($cmd);
exec($cmd);

// We are done

addline("Completed\n");

if (!$p['stay_open']) {
	quitme();
	}

?>