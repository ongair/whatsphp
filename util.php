<?php

  date_default_timezone_set('Africa/Nairobi');
  
  function l($message)
  {
    echo date("H:i:s")." ".$message."\r\n";
  }

  function d($object)
  {
    var_dump($object);
  }

  function get_phone_number($jid)
  {
    return explode("@", $jid)[0]; 
  }

  function post_data($url, $data)  
  {
    // $url = 'http://0.0.0.0:3000/messages';
    // $data = array('account' => $me, 'message' => array( 'text' => $body, 'phone_number' => get_phone_number($from), 'message_type' => 'Text', 'whatsapp_message_id' => $id, 'name' => $name) );
    $headers = array('Content-Type' => 'application/json', 'Accept' => 'application/json');
    Requests::post($url, $headers, json_encode($data));
  }