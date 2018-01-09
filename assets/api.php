<?php
/*
API deals with replying to AJAX requests.
*/

//include all necessary files for artifact creation and create artifacts
include 'parser.php';
include 'artifact.php';

$pageDirectory = '../pages';

$parser = new Parser();

$artifacts = array();

createArtifacts();
formatArtifacts();

//check if artifact exists (returns true/false)
if ($_GET['request'] === 'verifyExistence') {
	if (getArtifact($_GET['artifact']) != null) {
		echo 'true';
		return;
	} else echo 'false';
}

//get link for given artifact (returns link tag if artifact exists)
if ($_GET['request'] === 'createArtifactLink') {
	$string = $_GET['artifact'];
	$style = 'class="neutral-link"';

	global $artifacts;

	if (getArtifact($string) != null) {
		echo '<a href="'.strtolower($string).'"'.$style.'>'.$string.'</a>';
		return;
	} else {
		echo 'Artifact does not exist.';
	}
}

//get attribute of artifact (returns formatted attribute of artifact, if artifact exists)
if ($_GET['request'] === 'getArtifactAttribute') {
	if (getArtifact($_GET['artifact']) != null) {
		echo getArtifact($_GET['artifact'])->attributes[$_GET['attribute']];
		return;
	} else {
		echo 'Artifact does not exist.';
	}
}
?>