/*
Requestscript holds javascript functions for sending request to API.
*/

//path to api.php
var apiPath = 'https://localhost:8080/assets/api.php';

function issueRequest(request, artifact, attribute) {
	var xhr = new XMLHttpRequest();
	if (attribute != undefined) xhr.open('GET', apiPath + '?request=' + request + '&artifact=' + artifact + '&attribute=' + attribute);
	else xhr.open('GET', apiPath + '?request=' + request + '&artifact=' + artifact);
	xhr.onload = function() {
		if (xhr.status === 200) {
			//handle response
			console.log(xhr.responseText);
		}
		else {
			//handle error
			console.log('Did not recieve reply from Purity.');
		}
	};
	xhr.send();
}