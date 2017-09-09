<?php
/*
Artifact is a data structure meant to hold a variety of properties and content describing a page.
The formatting of its attributes is done through the parser, to keep artifacts as minimal as possible.

Technically, an artifact has the capacity to carry any single-data attribute by simply adding it to the $attributes array.
The new attribute's formatting, of course, must be implemented into the parser, however.
*/
class Artifact {
	//list of attributes to check for in the file
	public $attributes = array(
		'name'=>'',
		'image'=>'',
		'image name'=>'',
		'github'=>'',
		'title'=>'',
		'content'=>'',
		'white'=>'',
		'path'=>'',
	);

	//tags carries a selection of tags, which can be used for grouping and organizational purposes. the array is retrieved from first to last - most important to least important
	public $tags = array();

	//constructor parses file to retrieve its contents
	public function __construct($filePath) {
		$file = fopen($filePath, 'r');

		if ($file) {
			$currentKey = null;

			while (($line = fgets($file)) !== false) {

				$multiline = true;

				//skip lines starting with '//' and empty lines
				if ((substr($line, 0, 2)) === '//' || $line === PHP_EOL) continue;

				//get tags (unique retrieval due to it being an array)
				if (substr($line, 0, 5) === 'tags:') {
					$this->tags = explode(',', trim(substr($line, 5, strlen($line))));
					$multiline = false;
				} else {
					//go through each attribute and see if line begins with its declaration
					foreach ($this->attributes as $key => $value) {
						if (substr($line, 0, strlen($key) + 1) === $key.':') {
							//once key has been found, update $currentKey, and get the line's value
							$currentKey = $key;
							$value = trim(substr($line, strlen($currentKey) + 1, strlen($line)));
							$this->attributes[$currentKey] = $value;
							$multiline = false;
						}
					}
				}

				//if key wasn't found, continue adding to the previously acquired attribute
				if ($multiline && $currentKey != null) {
					if (strlen($line) == 3 && substr($line, 0, 1) === '+') $this->attributes[$currentKey] = $this->attributes[$currentKey].'<br>';
					else $this->attributes[$currentKey] = $this->attributes[$currentKey].$line;
				}
			}
		}
		fclose($file);
	}

	//returns true if artifact has tag ($string)
	public function hasTag($string) {
		for ($i = 0; $i < sizeof($this->tags); $i++) {
			if (strtolower($this->tags[$i]) === strtolower($string)) return true;
		}
		return false;
	}
}

//creates all artifacts by passing artifact constructor the artifact declaration file
function createArtifacts() {
	global $artifacts;
	global $pageDirectory;

	if ($pageDirectory) $dir = $pageDirectory.'/';

	if ($handle = opendir($dir)) {
		while (($file = readdir($handle)) !== false) {
			if (!in_array($file, array('.htaccess', '.', '..')) && !is_dir($dir.$file)) {
				array_push($artifacts, new Artifact($dir.$file));
			}
		}
	}
}

//custom comparison for ordering artifacts by name
function artifactComparison($a, $b) {
    return strcmp(strtolower($a->attributes['name']), strtolower($b->attributes['name']));
}

//goes through all artifacts and uses parser to format their attributes
function formatArtifacts() {
	global $artifacts;
	global $parser;

	//sort artifacts alphabetically
	usort($artifacts, "artifactComparison");

	if ($parser && $artifacts) {
		for ($i = 0; $i < sizeof($artifacts); $i++) {
			$parser->firstFormat($artifacts[$i]);
		}
		for ($i = 0; $i < sizeof($artifacts); $i++) {
			$parser->secondFormat($artifacts[$i]);
		}
	}
}

//finds artifact by name
function getArtifact($string) {
	global $artifacts;

	if ($artifacts) {
		for ($i = 0; $i < sizeof($artifacts); $i++) {
			if (strtolower($artifacts[$i]->attributes['name']) === $string) {
				return $artifacts[$i];
			}
		}
	}
	return null;
}
?>