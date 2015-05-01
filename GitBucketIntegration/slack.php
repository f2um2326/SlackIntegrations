<?php

function parse_commits($payload){
	$repository = $payload['repository'];
	$commits = $payload['commits'];
	$color = '#4183C4';

	$repo_full_name = $repository['full_name'];
	$branch  = str_replace('refs/heads/', '', $payload['ref']);


	$num_commits = count($commits);
	$committer_ids = [];
	foreach($commits as $commit){
		array_push($committer_ids, $commit['committer']['name']);
	}

	# make "pretext"
	$pretext_prefix = sprintf("[%s:%s]", $repo_full_name, $branch);
	$num_unique_ids = count(array_unique($committer_ids));
	if($num_unique_ids == 1){
		$committer_id = $committer_ids[0];
		$pretext_body = sprintf("%d new commit", $num_commits) .
						($num_commits > 1 ? "s" : "") .
						sprintf(" by %s:", $committer_id);
	}
	else{
		$pretext_body = sprintf("%d new commits:", $num_commits);	
	}
	$pretext = sprintf("%s %s", $pretext_prefix, $pretext_body);

	# make "fields"
	$fields = [];
	foreach ($commits as $commit) {
		$commit_url = $commit['html_url'];
		$commit_id_short = substr($commit['id'], 0, 7); # first 7 letters of the hash value
		$commit_link = "<" . sprintf("%s|%s", $commit_url, $commit_id_short) . ">";
		$commit_message = $commit['message'];
		$message_1st_line = substr($commit_message, 0, strpos($commit_message, "\n"));
		$committer_id = $commit['committer']['name'];
		$value = sprintf("%s: %s - %s", $commit_link, $message_1st_line, $committer_id);
		$field = array('value' => $value, 'short' => false);
		array_push($fields, $field);
	}

	$attachment = array();
	$attachment["fallback"] = $pretext;
	$attachment['pretext'] = $pretext;
	$attachment['fields'] = $fields;
	$attachment['color'] = $color;
		
	return array('attachments' => [$attachment]);
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

	return array('attachments' => [$attachment]);
}

function parse_pull_request($payload){
	$repository = $payload['repository'];
	$pull_request = $payload['pull_request'];
	$action = $payload['action'];
	$sender_name = $payload['sender']['login'];
	$number = $pull_request['number'];
	$title = $pull_request['title'];
	$html_url = $pull_request['html_url'];
	$body = $pull_request['body'];
	$color = '#6CC644';

	$pretext_prefix = sprintf("[%s] Pull request", $repository['full_name']);
	$title_text = sprintf("#%d %s", $number, $title);
	$attachment = array();

	# Commit to pull request branch
	if($action == "synchronize") {
		# Nothing to do
		return null;
	}
	# Pull request open
	else if($action == "opened") {
		$pretext = sprintf("%s submitted by %s",
						   $pretext_prefix, $sender_name);
		$text = $body;
		$fallback = $pretext; 
		$attachment['pretext'] = $pretext;
		$attachment['title'] = $title_text;
		$attachment['title_link'] = $html_url;
		$attachment['color'] = $color;
	}
	else{
		$linked_title_text = "<" . sprintf("%s|%s", $html_url, $title_text) . ">";
		# Pull request closed (= merged.)
		if($action == "closed") {
			$text = sprintf("%s closed: %s by %s", $pretext_prefix,
							$linked_title_text, $sender_name);
		}
		# Pull request reopened
		else if($action == "reopened") {
			$text = sprintf("%s re-opened: %s by %s", $pretext_prefix,
							$linked_title_text, $sender_name);
			$attachment['color'] = $color;
		}
		$fallback = $text;
	}
	$attachment['fallback'] = $fallback;
	$attachment["text"] = $text;

	return array('attachments' => [$attachment]);
}

function main(){
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
		if($post == null){
			return 0;
		}
	}

	$post['username'] ='GitBucket Bot';
	$post['icon_emoji'] = ':gitbucket:';

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

main();

?>
