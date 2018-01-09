<?php
/*
Static holds functions relevant to exporting a static instance of Purity.
*/

function exportStatic() {
	global $artifacts;

	//make directory for export
	if (!file_exists('static')) mkdir('static');

	//create file for each artifact
	for ($i = 0; $i < sizeof($artifacts); $i++) {
		$artifact = $artifacts[$i];
		$file = 'static/' . $artifact->attributes['name'] . '.html';

		if (!file_exists($file)) {
			$handle = fopen($file, 'w') or die('Cannot open file: ' . $file);
			ob_start();
			include 'assets/template.php';
			$data = ob_get_contents();
			ob_end_clean();
			fwrite($handle, $data);
		}
	}
}
?>