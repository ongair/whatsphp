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

  // $count = Account::count()
  $count = Account::count(array('conditions' => 'setup = true'));
  $error = getenv('ERROR') == 'true';

  $app->render(200, array(
    'running' => true,
    'error' => $error,
    'active' => $count));
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
      $w->codeRequest($mode, $carrier);
      $message = 'Code requested';
    } 
    catch(Exception $ex) {
      $message = $ex->getMessage();
      $error = true;
    }
  }

  $app->render(200, array(
    'error' => $error,
    'message' => $message
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

    $w = new WhatsProt($username, $identity, $nickname, false);
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




$app->run();
