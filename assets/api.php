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

$req = '';
$art = '';
$att = '';

if ($_GET['request']) $req = htmlspecialchars($_GET['request'], ENT_QUOTES, 'UTF-8');
if ($_GET['artifact']) $art = htmlspecialchars($_GET['artifact'], ENT_QUOTES, 'UTF-8');
if ($_GET['attribute']) $att = htmlspecialchars($_GET['attribute'], ENT_QUOTES, 'UTF-8');

//check if artifact exists (returns true/false)
if ($req === 'verifyExistence') {
	if (getArtifact($art) != null) {
		echo 'true';
		return;
	} else echo 'false';
}

//get link for given artifact (returns link tag if artifact exists)
if ($req === 'createArtifactLink') {
	$string = $art;
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
if ($req === 'getArtifactAttribute') {
	if (getArtifact($art) != null) {
		echo getArtifact($art)->attributes[$att];
		return;
	} else {
		echo 'Artifact does not exist.';
	}
}
?>