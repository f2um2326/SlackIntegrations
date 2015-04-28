<?php

function parse_commits($payload){
	$text  = 'Name: ' . $payload['repository']['owner']['login'];
	$text .= ', Repository: ' . $payload['repository']['name'];
	$branch  = str_replace('refs/heads/', '', $payload['ref']);
	$text .= ', Branch: ' . $branch . "\n";
	foreach ($payload['commits'] as $commit) {
		$text .= 'Comment: ' . $commit['message'];

		$text .= count($commit['added']) . ' added: ';
		$text .= join(", ", $commit['added']);
		$text .= ', ';

		$text .= count($commit['removed']) . ' removed: ';
		$text .= join(", ", $commit['removed']);
		$text .= ', ';

		$text .= count($commit['modified']) . ' modified: ';
		$text .= join(", ", $commit['modified']);
		$text .= ', ';

		$text .= "\n";
		$text .= $commit['html_url'] . "\n";
	}

	return array('text' => $text,
				 'username' => 'GitBucket Bot');
}

function parse_issue($payload){
	$issue = $payload['issue'];
	$action = $payload['action'];
	$sender = $payload['sender']['login'];
	$body = $issue['body'];
	$number = $issue['number'];
	$title = $issue['title'];
	$title_link = $payload['repository']['html_url'] . "/issues/" . $number;

	$pretext = '[' . $payload['repository']['name'] . '] ';

	$attachment = array();
	# closed
	if($action == "closed") {
		$link = '<' . $title_link . '|#' . $number . ' ' . $title . '>';
		$text = $pretext . 'Issue closed: ' . $link . ' by ' . $sender;
		$pretext .= 'Issue closed: ' . $title . ' by ' . $sender;
	}
	else{
		# opened
		if($action == "opened") {
			$pretext .= 'Issue created by ' . $sender;
			$text = $body;
		}
		# reopened
		else if($action == "reopened") {
			$pretext .= 'Issue reopened by ' . $sender;
			$text = $body;
		}
		# "created" also called when added comment on pull request
		else if($action == "created") {
			$pretext .= 'Issue commented by ' . $sender;
			$text = $payload['comment']['body'];
		}	

		$attachment['pretext'] = $pretext;
		$attachment['title'] = '#' . $number . ' ' . $title;
		$attachment['title_link'] = $title_link;
		$attachment['color'] = '#F29513';
	}

	$attachment["fallback"] = $pretext;
	$attachment["text"] = $text;

	return array('attachments' => [$attachment],
				 'username' => 'GitBucket Bot');
}

function parse_pull_request($payload){
	$pull_request = $payload['pull_request'];
	$action = $payload['action'];
	$title = $pull_request['title'];

	$text  = 'Name: ' . $payload['repository']['owner']['login'];
	$text .= ', Repository: ' . $payload['repository']['name'];
	$branch = $pull_request['head']['ref'];
	$text .= ', Branch: ' . $branch . "\n";

	# Pull request open
	if($action == "opened") {
		$text .= 'New Pull Request Opened: ' . $title . "\n";
		$text .= 'Comment: ' . $pull_request['body'] . "\n";
	}
	# Commit to pull request branch
	if($action == "synchronize") {
		$text .= 'New Commit at ' . $branch . ' by ' . $payload['sender']['login'] . ' [Pull request branch]' . "\n";
	}
	# Pull request closed (= merged.)
	if($action == "closed") {
		$text .= 'Closed pull request: ' . $title . "\n";
	}
	# Pull request reopened
	if($action == "reopened") {
		$text .= 'Reopened pull request: ' . $title . "\n";
	}
	$text .= $pull_request['html_url'] . "\n";

	return array('text' => $text,
				 'username' => 'GitBucket Bot');
}

function main($_REQUEST){
	# do nothing if $_REQUEST['payload'] is empty
	if(empty($_REQUEST['payload'])){
		return 0;
	}

	$payload = json_decode($_REQUEST['payload'], true);

	# about push, commit
	if(isset($payload['commits'])) {
		$firewebhook = 1;
		$post = parse_commits($payload);
	}
	# about issue
	else if(isset($payload['issue'])) {
		$firewebhook = 1;
		$post = parse_issue($payload);
	}
	# about pull request
	else if(isset($payload['pull_request'])) {
		$firewebhook = 1;
		$post = parse_pull_request($payload);
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

main($_REQUEST['payload']);

?>
