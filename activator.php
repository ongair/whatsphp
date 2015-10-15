<?php
  require 'vendor/autoload.php';
  Dotenv::load(__DIR__);
  
  $username = $argv[1];
  $service = "whatsapp-".$username;

  
  $target = "/etc/init/".$service.'.conf';
  $copied = copy("service.template", $target);

  $raw = file_get_contents($target);
  $raw = str_replace("DIR", getenv('CWD'), $raw);
  $raw = str_replace("ACCOUNT", $username, $raw);
  file_put_contents($target,$raw);