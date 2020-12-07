<?php
//get artifact to load
if (isset($_GET['v'])) {
	if ($_GET['v']) {
		$v = strtolower($_GET['v']);
	} else $v = 'index';
} else {
	$_GET['v'] = 'index';
	$v = $_GET['v'];
}

include 'assets/parser.php';
include 'assets/artifact.php';
include 'assets/customartifact.php';

//name of directory for artifact declarations
$pageDirectory = 'pages';

//single parser for all artifacts
$parser = new Parser();

//array holding artifacts
$artifacts = array();

//creates and formats artifacts
createArtifacts();
formatArtifacts();

//load artifact
if (getArtifact($v) != null) $artifact = getArtifact($v);
else if (substr($v, 0, 4) === "404-") {
	//if slashes remain, sanitize and redirect
	if (strstr($v, '/')) {
		redirect(substr($v, 4, $v.length));
	}

	//create 404
	$name = substr($v, 4, $v.length);
	$artifact = new CustomArtifact();
	$artifact->attributes['name'] =  "404 - " . $name;
	$artifact->attributes['image'] = "404>1";
	$artifact->attributes['image name'] = "#[404]";
	$artifact->attributes['white'] = "true";
	$artifact->attributes['title'] = "Artifact _[" . $name . "] not found.";
	$artifact->attributes['content'] = "This is the 404 page.";
	$artifact->path = "home";
	$parser->firstFormat($artifact);
	$parser->secondFormat($artifact);
} else {
	//if artifact doesn't exist, load 404
	redirect($v);
}

//get template
ob_start();
include 'assets/template.php';
$page = ob_get_contents();
ob_end_clean();
echo $page;

function redirect($search) {
	$search = sanitize($search);
	header('Location: https://YOUR_SITE/404-' . $search);
	die();
}

function sanitize($string) {
	$string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
	$string = htmlspecialchars($string, ENT_QUOTES);
	return $string;
}
?>