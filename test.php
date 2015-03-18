<?php

  require_once('util.php');  
  require 'vendor/autoload.php';

  Dotenv::load(__DIR__);

  $username = $argv[1];
  $password = $argv[2];
  $nickname = $argv[3];

  l('Username: '.$username);
  l('Password: '.$password);
  l('Nickname: '.$nickname);

  sleep(5);

  l('Exiting');

  exit(1);