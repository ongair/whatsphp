<?php

  require_once('models/Account.php');  
  require_once('models/Asset.php');  
  require_once('models/JobLog.php');
  require_once('events.php');  
  
  class Client
  {
    private $account;
    private $password;
    private $nickname;
    private $wa;
    private $connected;

    function __construct($account, $password, $nickname) {
      $this->account = $account;
      $this->password = $password;
      $this->nickname = $nickname;
      $this->connected = false;
      $this->identity = "";
      $debug = (bool) getenv('DEBUG');

      chdir(getenv('CWD'));

      init_log($account);

      $this->_init_db();
      $this->account_id = $this->get_account_id();          

      l('Logging in');

      if ($this->is_active()) {
        $this->wa = new WhatsProt($this->account, $this->identity, $this->nickname, $debug);
        $events = new Events($this, $this->wa);
        $events->setEventsToListenFor($events->activeEvents);        
      }       
    }

    public function loop() {
      if (!$this->connected) {
        
        try
        {
          $this->wa->connect();
          $this->wa->loginWithPassword($this->password);          

          $start = microtime(true);
          $secs = 0;
          $timeout = intval(getenv('TIMEOUT'));
          
          while($secs < $timeout) {
            
            $this->wa->pollMessage(true);
            $this->get_jobs();

            $mid = microtime(true);
            $secs = intval($mid - $start);
            l('Disconnect in: '.($timeout - $secs));
          }

          $end = microtime(true);
          $timediff = intval($end - $start);
          $this->wa->disconnect();  
        }
        catch(ConnectionException $e) {
          l('Error occurred when trying to connect '.$e->getMessage());
          exit(0);
        }        
      }      
    }

    public function toggleConnection($status) {
      $this->connected = $status;
    }

    private function get_jobs() {
      $jobs = JobLog::all(array('sent' => false, 'account_id' => $this->account_id, 'pending' => false));
      l("Num jobs ".count($jobs));

      foreach ($jobs as $job) {        
        $this->do_job($job);
        l($job->method);
      }
    }

    private function do_job($job) {
      if ($job->method == "sendMessage") {
        $this->send_message($job);
      }
      elseif ($job->method == "sendImage") {
        $this->send_image($job);
      }     
      elseif ($job->method == "broadcast_Text") {
        $this->broadcast_text($job);
      }  
      elseif ($job->method == "group_create") {
        $this->create_group($job);
      }
      elseif ($job->method == "group_addParticipants") {
        // $this->add_participants_to_group($job);
      }
      else {
        l('Job is '.$job->method);
      }
    }

    /**
     * Called to add participants to an existing group
     */
    private function add_participants_to_group($job) {
      $members = explode(',', $job->targets);
      
      $this->wa->sendGroupsParticipantsAdd($job->args, $targets);
    }

    private function create_group($job) {
      l('Going to create group '.$job->args);
      l('Will add participants '.$job->targets);

      $group_name = $job->args;
      $members = explode(',', $job->targets);
      
      $groupJid = $this->wa->sendGroupsChatCreate($group_name, $members);
      l('Group id '.$groupJid);

      $group = Group::find_by_id($job->group_id);
      $group->jid = $groupJid;
      $group->active = true;
      $group->save();
      
      $url = getenv('URL').'/groups/'.$group->id.'/activate_members';
      l('Posting to url '.$url);

      // post_data      
      $data = array('members' => $job->targets, 'account' => $this->account);
      post_data($url, $data);

      $job->sent = true;
      $job->save();
    }

    private function broadcast_text($job) {
      $job->whatsapp_message_id = $this->wa->sendBroadcastMessage(explode(',',$job->targets), $job->args);
      $job->sent = true;
      $job->save();
    }

    private function send_message($job) {
      $id = $this->wa->sendMessage($job->targets, $job->args);      
      $job->whatsapp_message_id = $id;
      $job->sent = true;
      $job->save();
    }

    private function send_image($job) {
      
      $args = explode(',', $job->args);
      $asset_id = $args[0];
      $asset_url = getenv('URL').$args[1];

      l('Url: '.$asset_url);


      $asset = Asset::find_by_id($asset_id);
      $file_name = 'tmp/'.$asset->file_file_name;

      l('File name: '.$file_name);

      if ($this->download($asset_url, $file_name)) {
        
        $job->whatsapp_message_id = $this->wa->sendMessageImage($job->targets, $file_name);
        $job->sent = true;
        $job->save();

      }
    }

    private function download($url, $dest) {
      try {
        $data = file_get_contents($url);
        $handle = fopen($dest, "w");
        fwrite($handle, $data);
        fclose($handle);
        return true;  
      } catch (Exception $e) {
        l('Caught exception: '.$e->getMessage());
        l('Exiting normally so we dont restart');
        exit(0);
      }
      return false;
    }

    public function get_account_id() {
      return Account::find_by_phone_number($this->account)->id;
    }

    public function get_account() {
      return $this->account;
    }

    private function is_active() {
      return Account::exists(array('phone_number' => $this->account, 'setup' => true));
    }

    private function _init_db() {
      $env = getenv('ENV');
      $db = getenv('DB');

      $cfg = ActiveRecord\Config::instance();      
      $cfg->set_default_connection($env);
      $cfg->set_model_directory('models');

      $cfg->set_connections(
        array(
          $env => $db
        )
      );
    }
  }