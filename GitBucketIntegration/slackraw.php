<?php
 $text  = $_REQUEST['payload'];

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
?>
