<?php
if(isset($_REQUEST['payload'])) {
	$firewebhook = 0;

	$payload = json_decode($_REQUEST['payload'], true);


	$text  = 'Name: ' . $payload['repository']['owner']['login'];
	$text .= ', Repository: ' . $payload['repository']['name'];

	if(isset($payload['ref'])) {
		$branch  = str_replace('refs/heads/', '', $payload['ref']);
		$text .= ', Branch: ' . $branch . "\n";
	}
	else if(isset($payload['pull_request'])) {
		$branch = $payload['pull_request']['head']['ref'];
		$text .= ', Branch: ' . $branch . "\n";
	}
	else {
		$text .= "\n";
	}

	# about push, commit
	if(isset($payload['commits'])) {
		$firewebhook = 1;
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

	# about issue
	if(isset($payload['issue'])) {
		$firewebhook = 1;
		if($payload['action'] == "opened") {
			$text .= 'Opened issue "' . $payload['issue']['title'] . '"' . "\n";
			$text .= 'Description: ' . $payload['issue']['body'] . "\n";
			$text .= $payload['repository']['html_url'] . "/issues/" . $payload['issue']['number'] . "\n";
		}
		if($payload['action'] == "reopened") {
			$text .= 'Reopened issue "' . $payload['issue']['title'] . '"' . "\n";
			$text .= 'Description: ' . $payload['issue']['body'] . "\n";
			$text .= $payload['repository']['html_url'] . "/issues/" . $payload['issue']['number'] . "\n";
		}
		# "created" also called when added comment on pull request
		if($payload['action'] == "created") {
			$text .= 'Commented on issue "' . $payload['issue']['title'] . '"' . ' [state: ' . $payload['issue']['state'] . ']' . "\n";
			$text .= 'Comment: ' . $payload['comment']['body'] . "\n";
			$text .= $payload['repository']['html_url'] . "/issues/" . $payload['issue']['number'] . "\n";
		}
		if($payload['action'] == "closed") {
			$text .= 'Closed issue "' . $payload['issue']['title'] . '"' . "\n";
			$text .= $payload['repository']['html_url'] . "/issues/" . $payload['issue']['number'] . "\n";
		}
	}

	# about pull request
	if(isset($payload['pull_request'])) {
		$firewebhook = 1;
		$pullrequest = $payload['pull_request'];

		# Pull request open
		if($payload['action'] == "opened") {
			$text .= 'New Pull Request Opened: ' . $pullrequest['title'] . "\n";
			$text .= 'Comment: ' . $pullrequest['body'] . "\n";
			$text .= $pullrequest['html_url'] . "\n";
		}

		# Commit to pull request branch
		if($payload['action'] == "synchronize") {
			$text .= 'New Commit at ' . $pullrequest['head']['ref'] . ' by ' . $payload['sender']['login'] . ' [Pull request branch]' . "\n";
			$text .= $pullrequest['html_url'] . "\n";
		}

		# Pull request closed (= merged)
		if($payload['action'] == "close") {
			$text .= 'Closed pull request: ' . $pullrequest['title'];
			$text .= $pullrequest['html_url'] . "\n";
		}

		# Pull request reopened
		if($payload['action'] == "reopened") {
			$text .= 'Reopened pull request: ' . $pullrequest['title'];
			$text .= $pullrequest['html_url'] . "\n";
		}
	}
	
	if( $firewebhook == 1) {
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
}
?>
