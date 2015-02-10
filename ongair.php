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
  // $timeout = intval($argv[4]);
  $identity = "";

  $client = new Client($username, $password, $nickname);



  // $cfg = ActiveRecord\Config::instance();
  // $cfg->set_default_connection('development');
  // $cfg->set_model_directory('models');


  // $cfg->set_connections(
  //   array(
  //     'development' => 'mysql://root:root@127.0.0.1:8889/ongair_prod'
  //   )
  // );


  // $w = new WhatsProt($username, $identity, $nickname, $debug);
  // $events = new Events($w);
  // $events->setEventsToListenFor($events->activeEvents);

  // l("About to connect");
  // $w->connect();
  
  // l("About to log in");
  // $w->loginWithPassword($password);

  
  // $start = microtime(true);

  // $secs = 0;
  // while($secs < $timeout) {
    
  //   $w->pollMessage(true);

  //   $mid = microtime(true);
  //   $secs = intval($mid - $start);
  //   l('Secs: '.$secs);
  // }

  // $end = microtime(true);
  // $timediff = intval($end - $start);

  // l('time: '.$timediff);
  

  // l("About to disconnect");
  // $w->disconnect();