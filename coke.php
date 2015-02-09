<?php

  require_once('lib/whatsapp/whatsprot.class.php');
  require_once('lib/requests/Requests.php');
  require_once('coke_events.php');
  require_once('util.php');

  Requests::register_autoloader();

  $username = $argv[1];
  $password = $argv[2];
  $nickname = $argv[3];
  $timeout = intval($argv[4]);
  $debug = false;

  l('Phone number: '.$username);
  l('Password: '.$password);
  l('Name: '.$nickname);
  l('Timeout: '.$timeout);

  $w = new WhatsProt($username, $identity, $nickname, $debug);
  $events = new CokeEvents($w);  
  $events->setEventsToListenFor($events->activeEvents);

  $w->connect();
  $w->loginWithPassword($password);
  
  $start = microtime(true);

  $secs = 0;
  while($secs < $timeout) {
    
    $w->pollMessage(true);

    $mid = microtime(true);
    $secs = intval($mid - $start);
    l('Secs: '.$secs);
  }

  $end = microtime(true);
  $timediff = intval($end - $start);

  l('time: '.$timediff);


  $w->disconnect();