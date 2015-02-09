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