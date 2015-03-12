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
    $split = explode("@", $jid);
    return $split[0];
  }

  function post_data($url, $data)
  {
    $headers = array('Content-Type' => 'application/json', 'Accept' => 'application/json');
    Requests::post($url, $headers, json_encode($data));
  }

  function _init_db() {
    $env = getenv('ENV');
    $db = getenv('DB');

    $cfg = ActiveRecord\Config::instance();
    $cfg->set_default_connection($env);
    $cfg->set_model_directory('models');

    $cfg->set_connections(
      array(
        $env => $db
      )
    );
  }
