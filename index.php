<?php

require 'vendor/autoload.php';
require_once('lib/whatsapp/whatsprot.class.php');
require_once('lib/activerecord/ActiveRecord.php');
require_once('models/Account.php');
require_once('util.php');

Dotenv::load(__DIR__);

$app = new \Slim\Slim();
$app->view(new \JsonApiView());
$app->add(new \JsonApiMiddleware());

// Application status
$app->get('/status', function () use ($app) {

  // attempt to connect to DB
  _init_db();

  $count = Account::count(array('conditions' => 'setup = true'));
  $error = getenv('ERROR') == 'true';

  $app->render(200, array(
    'running' => true,
    'error' => $error,
    'active' => $count));
});

$app->get('/account/:id/status', function ($id) use ($app) {

  _init_db();
  $account = Account::find_by_phone_number($id);

  if (!$account == null) {

    $online = true;
    if (is_production()) {
      $online = service_running($id);
    }

    $app->render(200, array(
      'phone_number' => $id,
      'name' => $account->name,
      'exists' => true,
      'online' => $online,
      'active' => (bool) $account->setup,      
      'beta_user' => (bool) $account->beta_user
    ));  
  }
  else {
    $app->render(200, array(
      'phone_number' => $id,
      'exists' => false      
    ));
  }

  
});

// Request an SMS code
$app->post('/request', function() use ($app) {

  $username = $app->request->params('phone_number');
  $nickname = $app->request->params('nickname');
  $carrier = $app->request->params('carrier');
  $mode = $app->request->params('mode');
  $debug = $app->request->params('debug') == 'true';
  $identity = $username;
  $message = null;
  $error = getenv('ERROR') == 'true';
  $retry_after = 1805;

  if ($mode == null || $mode == '')
    $mode = getenv('MODE');

  if ($debug) {        
    $message = 'Code requested';
    if ($error) {
      $message = 'No routes';
      $error = true;
    }
  }
  else {
    try {
      $w = new WhatsProt($username, $nickname, false);
      $response = $w->codeRequest($mode, $carrier);
      $message = 'Code requested';
      $retry_after = $response->retry_after;
    } 
    catch(Exception $ex) {
      $message = $ex->getMessage();
      $error = true;
    }
  }

  $app->render(200, array(
    'error' => $error,
    'message' => $message,
    'retry_after' => $retry_after
  ));

});

$app->post('/register', function() use ($app) {

  $username = $app->request->params('phone_number');
  $nickname = $app->request->params('nickname');
  $code = $app->request->params('code');
  $debug = $app->request->params('debug') == 'true';
  $identity = $username;
  $message = null;
  $password = null;
  $error = false;

  if ($debug) {
    $password = "1234567890wa+";
    $message = 'Activated';
  }
  else {

    $w = new WhatsProt($username, $nickname, false);
    try {
      $result = $w->codeRegister($code);
      $message = 'Activated';
      $password = $result->pw;
    } 
    catch(Exception $e) {
      $message = $e->getMessage();
    }
  }

  $app->render(200, array(
    'error' => $error,
    'message' => $message,
    'password' => $password
  ));

});

$app->post('/activate', function() use ($app) {

  $username = $app->request->params('phone_number');
  $name = $app->request->params('nickname');
  $debug = $app->request->params('debug') == 'true';
  $service = "whatsapp-".$username;
  // getServiceName($name);
  $target = "tmp/services/".$service.'.conf';

  if ($debug) {
    $app->render(200, array(
        'error' => false,
        'service' => $service,
        'target' => $target
      ));
  }
  else {
    $copied = copy("service.template", $target);

    $raw = file_get_contents($target);
    $raw = str_replace("DIR", getenv('CWD'), $raw);
    $raw = str_replace("ACCOUNT", $username, $raw);
    file_put_contents($target,$raw);

    $app->render(200, array(
      'error' => !$copied,
      'service' => $service,
      'target' => $target
    ));  
  }
  
});




$app->run();
