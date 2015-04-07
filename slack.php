<?php
  $payload = json_decode($_REQUEST['payload'], true);
  $branch  = str_replace('refs/heads/', '', $payload['ref']);
  $text  = $payload['pusher']['name'] . ' reflects differences in repository ' . $payload['repository']['name'] . '.' . "\n";
  foreach ($payload['commits'] as $commit) {
    $text .= substr($commit['url'], 0, 43) . substr($commit['url'], 50) . "\n";
  }

  if(isset($_GET['webhook'])) {
    $webhook = $_GET['webhook'];
  }

  if(isset($_GET['channel'])) {
    $channel = '#' . $_GET['channel'];
  }

  $post = array(
    'text'       => $text,
    'username'   => 'Incoming WebHooks',
    'channel'    => $channel,
  );

  $ch = curl_init('https://hooks.slack.com/services/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');

  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array());
  curl_setopt($ch, CURLOPT_POSTFIELDS, array('payload' => json_encode($post)));
  curl_exec($ch);
  curl_close($ch);
?>