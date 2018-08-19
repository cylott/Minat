<?

// MinatPrefs 0.1.0
// 

// Includes

require (__DIR__."/functions.php");
require (__DIR__."/functions.pashua.php");

// Read Prefs

$p = unserialize(file_get_contents(__DIR__."/prefs.php"));
if(!$p['destpath']) {
	$p['destpath'] = "/Users/".get_current_user()."/Desktop";
	}

// Load strings

$strings[] = array("Temp","Inline","Custom");
$strings[] = array("Do nothing","Quicklook","Show in Finder","Open with handler...");
$strings[] = array("Rename","Transcode","Spectrogram");

$result = Pashua::showDialog(makeWindowString($p, $strings));

// User cancelled

if (@$result['cb']) {
	echo "0";
	die;
	}

// Fix strings

$result['dest'] = array_search($result['dest'],$strings[0]);
$result['postflight'] = array_search($result['postflight'],$strings[1]);
$result['mode'] = array_search($result['mode'],$strings[2]);
$result['spec_dims'] = array($result['spec_dims_x'],$result['spec_dims_y']);

// If the user didn't specify a destpath, set to desktop

if(!$p['destpath']) {
	$p['destpath'] = "/Users/".get_current_user()."/Desktop";
	}

// Fix a fucking Pashua bug

$result['destpath'] = str_replace("Desktop/Desktop","Desktop",$result['destpath']);

// Write Prefs

file_put_contents("prefs.php",serialize($result));
echo "1";

?>