<?php

  require_once('lib/whatsapp/whatsprot.class.php');
  require_once('lib/activerecord/ActiveRecord.php');
  require_once('lib/requests/Requests.php');
  require_once('util.php');
  require_once('events.php');
  
  Requests::register_autoloader();

  $debug = false;
  $phone_number = $argv[1];
  $password = $argv[2];
  $name = $argv[3];
  $timeout = intval($argv[4]);
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