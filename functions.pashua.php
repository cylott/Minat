<?

// Pashua Stuff

/**
 * Static class which wraps the two simple methods used for communicating with Pashua
 */
class Pashua
{
    /**
     * Invokes a Pashua dialog window with the given window configuration
     *
     * @param string $conf           Configuration string to pass to Pashua
     * @param string $customLocation Filesystem path to directory containing Pashua
     *
     * @throws \RuntimeException
     * @return array Associative array of values returned by Pashua
     */
    public static function showDialog($conf, $customLocation = null)
    {
        if (ini_get('safe_mode')) {
            $msg = "To use Pashua you will have to disable safe mode or " .
                "change " . __FUNCTION__ . "() to fit your environment.\n";
            fwrite(STDERR, $msg);
            exit(1);
        }

        // Write configuration string to temporary config file
        $configfile = tempnam('/tmp', 'Pashua_');
        if (false === $fp = @fopen($configfile, 'w')) {
            throw new \RuntimeException("Error trying to open $configfile");
        }
        fwrite($fp, $conf);
        fclose($fp);

        $path = __DIR__."/Pashua.app/Contents/MacOS/Pashua";

        // Call pashua binary with config file as argument and read result
        $result = shell_exec(escapeshellarg($path) . ' ' . escapeshellarg($configfile));

        @unlink($configfile);

        // Parse result
        $parsed = array();
        foreach (explode("\n", $result) as $line) {
            preg_match('/^(\w+)=(.*)$/', $line, $matches);
            if (empty($matches) or empty($matches[1])) {
                continue;
            }
            $parsed[$matches[1]] = $matches[2];
        }

        return $parsed;
    }
}

function makeWindowString($p, $strings) {

	$conf = "
	# Set window title
	*.title = Preferences
	*.floating = 1

	hr.type = image
	hr.path = ".__DIR__."/hr.png"."
	hr.width = 320
	hr.height = 2
	hr.x = 0
	hr.y = 564

	mode.type = popup
	mode.label = Minat Operation
	mode.option = ".$strings[2][0]."
	mode.option = ".$strings[2][1]."
	mode.option = ".$strings[2][2]."
	mode.default = ".$strings[2][$p['mode']]."
	mode.width = 320
	mode.rely = 20
	mode.disabled = 1

	dest.type = popup
	dest.label = Destination
	dest.option = ".$strings[0][0]."
	dest.option = ".$strings[0][1]."
	dest.option = ".$strings[0][2]."
	dest.default = ".$strings[0][$p['dest']]."
	dest.width = 160

	destpath.type = openbrowser
	destpath.filetype = directory
	destpath.label = Custom destination
	destpath.default = ".$p['destpath']."
	destpath.width = 320
	
	lameopts.type = textfield
	lameopts.label = LAME flags
	lameopts.default = ".$p['lameopts']."
	lameopts.placeholder = -h -b 320 --ignore-tag-errors
	lameopts.width = 320

	check.type = checkbox
	check.label = Check flacs
	check.default = ".$p['check']."	

	warn_art.type = checkbox
	warn_art.label = Warn for missing art
	warn_art.default = ".$p['warn_art']."
	warn_art.x = 120
	warn_art.y = 338

	template.type = textfield
	template.label = Rename template
	template.default = ".$p['template']."
	template.placeholder = ^ARTIST^ - ^YEAR^ - ^ALBUM^ {LABEL CAT} [FORMAT]
	template.width = 320
	template.disabled = 1

	spec_dims_x.type = textfield
	spec_dims_x.label = Spec dimensions
	spec_dims_x.default = ".$p['spec_dims'][0]."
	spec_dims_x.width = 60
	spec_dims_x.disabled = 1

	spec_dims_y.type = textfield
	spec_dims_y.default = ".$p['spec_dims'][1]."
	spec_dims_y.width = 60
	spec_dims_y.x = 80
	spec_dims_y.y = 214
	spec_dims_y.disabled = 1
	
	postflight.type = popup
	postflight.label = When finished
	postflight.option = ".$strings[1][0]."
	postflight.option = ".$strings[1][1]."
	postflight.option = ".$strings[1][2]."
	postflight.option = ".$strings[1][3]."
	postflight.default = ".$strings[1][$p['postflight']]."
	postflight.width = 160
	
	handler.type = openbrowser
	handler.filetype = app
	handler.label = Handler application
	handler.default = ".$p['handler']."
	handler.width = 320
	
	stay_open.type = checkbox
	stay_open.label = Stay open
	stay_open.default = ".$p['stay_open']."	

	ding.type = checkbox
	ding.label = Ding
	ding.default = ".$p['ding']."
	ding.x = 100
	ding.y = 48

	cb.type = cancelbutton
	db.type = defaultbutton
	db.label = Save
	";
	
	return $conf;
	
	}

?>