<?php

  require_once('lib/whatsapp/whatsprot.class.php');
  require_once('util.php');
  require_once('events.php');

  $debug = false;
  $username = "254772246595";
  $password = "Pt3FclgdBmhWJx/60hPCof7n2SA=";
  $nickname = "Coke";
  $identity = "";

  $w = new WhatsProt($username, $identity, $nickname, $debug);
  $events = new Events($w);
  $events->setEventsToListenFor($events->activeEvents);

  l("About to connect");
  $w->connect();
  
  l("About to log in");
  $w->loginWithPassword($password);

  
  // poll for messages
  $w->pollMessage();
  

  l("About to disconnect");
  $w->disconnect();