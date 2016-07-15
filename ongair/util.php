<?php

  use Analog\Handler\File;

  function l($message)
  {
    echo date("H:i:s")." ".$message."\r\n";
    Analog::log ($message);
  }

  function init_log($account) {
    $env = getenv('env');
    $log_file = '/var/log/ongair.'.$account.'.'.$env.'.log';
    Analog::handler (Analog\Handler\File::init ($log_file));
  }

  function split_jid($jid) {
    $split = explode("@", $jid);
    return $split[0];
  }

  function get_phone_number($jid)
  {
    return split_jid($jid);
  }