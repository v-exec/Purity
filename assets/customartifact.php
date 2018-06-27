<?php
/*
CustomArtifact is an identical data structure to Artifact, except it is constructed through code-data, as opposed to using a file.

Documentation can be found in artifact.php.
*/
class CustomArtifact {
	public $attributes = array(
		'name'=>'',
		'image'=>'',
		'image name'=>'',
		'title'=>'',
		'content'=>'',
		'white'=>''
	);

	public $tags = array();
	public $links = array();
	public $path = array();
	public $brokenPath = array();

	public function __construct() {
		//
	}

	public function hasTag($string) {
		for ($i = 0; $i < sizeof($this->tags); $i++) {
			if (strtolower($this->tags[$i]) === strtolower($string)) return true;
		}
		return false;
	}
}
?>