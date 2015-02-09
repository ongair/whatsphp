<?php

  require_once('lib/whatsapp/whatsprot.class.php');
  require_once('coke_events.php');
  require_once('util.php');

  $phone_number = $argv[1];
  $password = $argv[2];
  $name = $argv[3];
  $timeout = intval($argv[4]);
  $debug = true;

  l('Phone number: '.$phone_number);
  l('Password: '.$password);
  l('Name: '.$name);
  l('Timeout: '.$timeout);

  $w = new WhatsProt($username, $identity, $nickname, $debug);
  $events = new CokeEvents($w);  
  $events->setEventsToListenFor($events->activeEvents);

  $w->connect();
  $w->loginWithPassword($password);
  
  $start = microtime(true);

  $secs = 0;
  while($secs < $timeout) {
    
    $w->pollMessage(false);

    $mid = microtime(true);
    $secs = intval($mid - $start);
    l('Secs: '.$secs);
  }

  $end = microtime(true);
  $timediff = intval($end - $start);

  l('time: '.$timediff);