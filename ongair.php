<?php

  require_once('lib/whatsapp/whatsprot.class.php');
  require_once('lib/activerecord/ActiveRecord.php');
  require_once('lib/requests/Requests.php');
  require_once('lib/phpdotenv/Dotenv.php');  
  require_once('client.php');
  require_once('util.php');
  require_once('events.php');
  
  Requests::register_autoloader();
  Dotenv::load(__DIR__);

  $debug = false;
  $username = $argv[1];
  $password = $argv[2];
  $nickname = $argv[3];

  $client = new Client($username, $password, $nickname);
  $client->loop();