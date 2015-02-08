<?php

  require_once('lib/whatsapp/whatsprot.class.php');
  require_once('lib/activerecord/ActiveRecord.php');
  require_once('lib/requests/Requests.php');
  require_once('util.php');
  require_once('events.php');
  
  Requests::register_autoloader();

  $debug = false;
  $username = "254772246595";
  $password = "Pt3FclgdBmhWJx/60hPCof7n2SA=";
  $nickname = "Coke";
  $identity = "";


  $cfg = ActiveRecord\Config::instance();
  $cfg->set_default_connection('development');
  $cfg->set_model_directory('models');

  // mysql://user:password@unix(/tmp/mysql.sock)/database

  $cfg->set_connections(
    array(
      'development' => 'mysql://root:root@127.0.0.1:8889/ongair_prod'
    )
  );


  $w = new WhatsProt($username, $identity, $nickname, $debug);
  $events = new Events($w);
  $events->setEventsToListenFor($events->activeEvents);

  l("About to connect");
  $w->connect();
  
  l("About to log in");
  $w->loginWithPassword($password);

  
  // poll for messages
  while($w->pollMessage()) {
    l("Polling for messages");
    sleep(1);
  }
  

  l("About to disconnect");
  $w->disconnect();