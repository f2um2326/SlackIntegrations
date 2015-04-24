<?php
if(isset($_REQUEST['payload'])) {
	$payload = json_decode($_REQUEST['payload'], true);


	$text  = 'Name: ' . $payload['repository']['owner']['login'];
	$text .= ', Repository: ' . $payload['repository']['name'];

	if(isset($payload['ref'])) {
		$branch  = str_replace('refs/heads/', '', $payload['ref']);
		$text .= ', Branch: ' . $branch . "\n";
	}
	else {
		$text .= "\n";
	}

	if(isset($payload['commits'])) {
		foreach ($payload['commits'] as $commit) {

			$text .= 'Comment: ' . $commit['message'];

			$text .= count($commit['added']) . ' added: ';
			foreach ($commit['added'] as $added) {
				$text .= $added;
				$text .= ', ';
			}
			if (count($commit['added']) == 0) {
				$text .= ', ';
			}

			$text .= count($commit['removed']) . ' removed: ';
			foreach ($commit['removed'] as $removed) {
				$text .= $removed;
				$text .= ', ';
			}
			if (count($commit['removed']) == 0) {
				$text .= ', ';
			}

			$text .= count($commit['modified']) . ' modified: ';
			foreach ($commit['modified'] as $modified) {
				$text .= $modified;
				$text .= ', ';
			}
			if (count($commit['modified']) == 0) {
				$text .= ', ';
			}

			$text .= "\n";
			$text .= $commit['html_url'] . "\n";
		}
	}
	else if(isset($payload['issue'])) {
		if(isset($payload['action'] == "opened") {
			$text .= 'Opened issue "' . $payload['issue']['title'] . '"' . "\n";
			$text .= 'Description: ' . $payload['issue']['body'] . "\n";
			$test .= $payload['repository']['html_url'] . '/issue/' . $payload['issue']['number'] . "\n";
		}
		if(isset($payload['action'] == "created") {
			$text .= 'Commented on issue "' . $payload['issue']['title'] . '"' . "\n";
			$text .= 'Comment: ' . $payload['comment']['body'] . "\n";
			$test .= $payload['repository']['html_url'] . '/issue/' . $payload['issue']['number'] . "\n";
		}
		if(isset($payload['action'] == "closed") {
			$text .= 'Closed issue "' . $payload['issue']['title'] . '"' . "\n";
			$test .= $payload['repository']['html_url'] . '/issue/' . $payload['issue']['number'] . "\n";
		}
	}

	if(isset($_GET['webhook'])) {
		$webhook = $_GET['webhook'];
	}


	$post = array(
		'text'       => $text,
	);


	$ch = curl_init($webhook);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array());
	curl_setopt($ch, CURLOPT_POSTFIELDS, array('payload' => json_encode($post)));
	curl_exec($ch);
	curl_close($ch);
}
?>
