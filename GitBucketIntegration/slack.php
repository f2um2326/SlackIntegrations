<?php
if(isset($_REQUEST['payload'])) {
	$payload = json_decode($_REQUEST['payload'], true);

	$firewebhook = 0;
	$usetext = 0;
	$userich = 0;
	$closeflag = 0;


	$text  = 'Name: ' . $payload['repository']['owner']['login'];
	$text .= ', Repository: ' . $payload['repository']['name'];

	$pretext = '[' . $payload['repository']['name'];

	if(isset($payload['ref'])) {
		$branch  = str_replace('refs/heads/', '', $payload['ref']);
		$text .= ', Branch: ' . $branch . "\n";
		$pretext .= ':' . $branch . '] ';
	}
	else if(isset($payload['pull_request'])) {
		$branch = $payload['pull_request']['head']['ref'];
		$text .= ', Branch: ' . $branch . "\n";
		$pretext .= ':' . $branch . '] ';
	}
	else {
		$text .= "\n";
		$pretext .= '] ';
	}

	$repurl = $payload['repository']['html_url'];
	$repname = $payload['repository']['name'];
	$repnameurl = '<' . $repurl . '|' . $repname . '>';

	# about push, commit
	if(isset($payload['commits'])) {
		$firewebhook = 1;
		$usetext = 1;
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
		$color = '#F29513';
		$sender = $payload['sender']['login'];

		$issuetitle = $payload['issue']['title'];
		$issueurl = $payload['repository']['html_url'] . "/issues/" . $payload['issue']['number'];
		$issuevalue = $payload['issue']['body'];
		$issuenumber = $payload['issue']['number'];
		$issuetitleurl = '<' . $issueurl . '|#' . $issuenumber . ' ' . $issuetitle . '>';

		$title = '#' . $issuenumber . ' ' . $issuetitle;
		$titlelink = $issueurl;

		# opened
		if($payload['action'] == "opened") {
			$userich = 1;
			$fallback = $pretext . 'Issue created by ' . $sender;
			$pretext = $pretext . 'Issue created by ' . $sender;
			$value = $issuevalue;
		}

		# reopened
		if($payload['action'] == "reopened") {
			$userich = 1;
			$fallback = $pretext . 'Issue reopened by ' . $sender;
			$pretext = $pretext . 'Issue reopened by ' . $sender;
			$value = $issuevalue;
		}

		# "created" also called when added comment on pull request
		if($payload['action'] == "created") {
			$userich = 1;
			$fallback = $pretext . 'Issue commented by ' . $sender;
			$pretext = $pretext . 'Issue commented by ' . $sender;
			$value = $payload['comment']['body'];
		}

		# closed
		if($payload['action'] == "closed") {
			$closeflag = 1;
			$fallback = $pretext . 'Issue closed: ' . $issuetitle . ' by ' . $sender;
			$value = $pretext . 'Issue closed: ' . $issuetitleurl . ' by ' . $sender;
		}
	}

	# about pull request
	if(isset($payload['pull_request'])) {
		$firewebhook = 1;
		$usetext = 1;
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

		# Pull request closed (= merged.)
		if($payload['action'] == "closed") {
			$text .= 'Closed pull request: ' . $pullrequest['title'] . "\n";
			$text .= $pullrequest['html_url'] . "\n";
		}

		# Pull request reopened
		if($payload['action'] == "reopened") {
			$text .= 'Reopened pull request: ' . $pullrequest['title'] . "\n";
			$text .= $pullrequest['html_url'] . "\n";
		}
	}

	if( $usetext == 1 ) {
		$post = array(
			'text'		=> $text,
			'username'	=>	'GitBucket Bot'
		);
	}
	
	if( $userich == 1 ) {
		$post = array(
			'attachments'       => [array(
				'fallback'	=>	$fallback,
				'pretext'	=>	$pretext,
				'title'		=>	$title,
				'title_link'	=>	$titlelink,
				'text'		=>	$value,
				'color'		=>	$color
			)],
			'username'	=>	'GitBucket Bot'
		);
	}

	if( $closeflag == 1 ) {
		$post = array(
			'attachments'       => [array(
				'fallback'	=>	$fallback,
				'text'		=>	$value
			)],
			'username'	=>	'GitBucket Bot'
		);
	}

	if( $firewebhook == 1) {
		if(isset($_GET['webhook'])) {
			$webhook = $_GET['webhook'];
		}

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
