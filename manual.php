<?php
  
  require_once('lib/whatsapp/whatsprot.class.php');
  require_once('util.php');
  require 'vendor/autoload.php';

  Dotenv::load(__DIR__);

  $username = $argv[1];
  $name = $argv[2];
  $password = $argv[3];
  $method = $argv[4];
  $args = $argv[5];

  init_log($username);

  l('Username: '.$username);
  l('Name: '.$name);
  l('Password: '.$password);
  l('Method: '.$method);
  l('Args: '.$args);

  if ($method == 'sendPromoteParticipants') {
    $wa = new WhatsProt($username, $name, true);
    $wa->connect();
    l('Connected');
    $wa->loginWithPassword($password);

    $params = explode(',', $args);
    $gjid = $params[0];
    $jid = $params[1];

    l('Group '.$gjid);
    l('User '.$jid);

    l('About to promote');
    $wa->sendPromoteParticipants($gjid, $jid);
    l('Promoted');
  }

