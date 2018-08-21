<?php

// Functions

function findCover($target) {

	// Try to find properly named files (prefer newest)

	$covers = array("Folder","Cover","Front","FOLDER","COVER","FRONT","folder","cover","front","AlbumArt");
	$exts = array("Jpg","Jpeg","JPG","JPEG","PNG","jpg","jpeg","png");
	
	$found = array();
	
	foreach ($covers as $cover) {
		foreach ($exts as $ext) {
			$try = $target."/".$cover.".".$ext;
			$tryparent = dirname($target)."/".$cover.".".$ext;
			if (file_exists($try)) {
				$found[filemtime($try)] = $try;
				}
			if (file_exists($tryparent)) {
				$found[filemtime($tryparent)] = $tryparent;
				}			
			}
		}
		
	// If no properly named file, find a single image
	
	if (!count($found)) {
		$result = glob($target."/*.{".implode(",",$exts)."}",GLOB_BRACE);
		if (@count($result) == 1) {
			$out = $result[0];
			}	
		} else {
		ksort($found);
		$out = end($found);
		}

	if ($out) {
		return $out;
		} else {
		return null;
		}
	
	}

function parseBindings($v, $tags) {
	foreach ($v as $flag => $value) {
		$out = null;
		if (is_array($value)) {
			foreach ($value as $item) {
				if (isset($tags[$item])) {
					$parts[] = $tags[$item];
					}
				}
			if (@count($parts)) {
				$out = implode("/",$parts);
				}
			} elseif (isset($tags[$value])) {
				$out = $tags[$value];
				}
		if ($out) {
			$line[] = array($flag,$out);
			}	
		}
	if (@$line) {
		return $line;
		} else {
		return null;
		}
	}

function lameFlagBuilder($string) {
	
	if (!$tags = parseVorbis($string)) {
		return "";
		}
	
	// LAME tag <--=--> VORBIS tag
	
	$v1["tt"] = "title";
	$v1["ta"] = "artist";
	$v1["tl"] = "album";
	$v1["ty"] = "date";
	$v1["tc"] = "comment";
	$v1["tn"] = array("tracknumber","tracktotal");

	$v2["tpe2"] = "albumartist";
	$v2["tpub"] = "label";
	$v2["tpos"] = array("discnumber","disctotal");
	$v2["tcmp"] = "compilation";
	$v2["tpe2"] = "album artist";
	$v2["tenc"] = "Minat";

	// Custom fields

	$v2b["catalognumber"] = "catalognumber";
	$v2b["original_encoded_by"] = "encoded_by";
	$v2b["original_encoder"] = "encoder";
	$v2b["original_encoding"] = "encoding";

	// V1 Bindings

	if ($v1parsed = parseBindings($v1,$tags)) {
		foreach ($v1parsed as $part) {
			$line[] = "--".$part[0]." ".escapeshellarg($part[1]);
			}
		}
	
	// V2 Bindings	

	if ($v2parsed = parseBindings($v2,$tags)) {
		foreach ($v2parsed as $part) {
			$line[] = "--tv ".escapeshellarg(strtoupper($part[0])."=".$part[1]);
			}
		}
		
	// V2 Chained Bindings (TXXX)

	foreach ($v2b as $flag => $value) {
		if (isset($tags[$value])) {
			$line[] = "--tv ".escapeshellarg("TXXX=".strtoupper($flag)."=".$tags[$value]);
			}
		}

	// backup original vorbis comment
	
	$line[] = "--tv ".escapeshellarg("TXXX=VORBIS_B64_ENCODED=".base64_encode(serialize($tags)));

	// String for lame

	return @implode(" ", $line);
	}

function parseVorbis($string) {
	preg_match_all("/(.*?)\s*=\s*(.*)/", $string, $matches);
	if (count($matches)) {
		foreach ($matches[1] as $key => $label) {
			$out[strtolower($label)] = $matches[2][$key];
			}
		return $out;
		} else {
		return 0;
		}
	}

function dirLabel($tags) {
	}

function getLogPath($tail = null) {
	return "/Users/".get_current_user()."/Library/Logs/".$tail;
	}

function addline($line, $file = null) {
	global $p;
	if (!$file) {
		$file = $p['logfile'];
		}
	file_put_contents($file, $line."\n", FILE_APPEND);
	}

function updateProgress($num = 0, $total = 100) {
	$percent = floor(($num/$total)*100);
	echo "\nPROGRESS:".$percent."\n";
	}
	
function updateStatus($string) {
	echo "\n".$string."\n";
	}

function alert($string, $title = "Warning") {
	echo "\nALERT:".$title."|".$string."\n";
	}

function ncenter($string, $title = "Minat") {
	exec("osascript -e 'display notification \"".$string."\" with title \"".$title."\"'");
	}

function getString($question) {
	return exec("osascript -e 'display dialog \"".$question."\" default answer \"\"' | cut -f3 -d\":\"");
	}

function ask($string) {
	$result = exec("osascript -e \"display dialog \\\"".$string."\\\"\" 2>&1");
	if (strpos($result,"canceled") !== false) {
		return 0;
		} else {
		return 1;
		}
	}

function askMulti($string, $buttons) {
	$buttonstring = "buttons {\\\"".implode("\\\", \\\"",$buttons)."\\\"} default button ".count($buttons);
	$result = exec("osascript -e \"display dialog \\\"".$string."\\\" ".$buttonstring."\" | cut -f2 -d':'");
	return array_search($result,$buttons);
	}

function quitme() {
	echo "\nQUITAPP\n";
	}

?>