<?php
  $payload = json_decode($_REQUEST['payload'], true);

 $branch  = str_replace('refs/heads/', '', $payload['ref']);
 $text  = 'Name: ' . $payload['pusher']['login'] . ', Repository: ' . $payload['repository']['name'] . ', Branch: ' . $branch . "\n";

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
         $text .= substr($commit['url'], 0, 43) . substr($commit['url'], 50) . "\n";
 }

  if(isset($_GET['webhook'])) {
    $webhook = $_GET['webhook'];
  }


  $post = array(
    'text'       => $text,
    'username'   => 'Incoming WebHooks'
  );


  $ch = curl_init($webhook);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array());
  curl_setopt($ch, CURLOPT_POSTFIELDS, array('payload' => json_encode($post)));
  curl_exec($ch);
  curl_close($ch);
?>