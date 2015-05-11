<?php

  require_once('lib/whatsapp/vCard.php');
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

    function __construct($account, $db_key = 'DB', $url_key = 'URL') {
      $this->account = $account;
      $this->connected = false;
      $debug = getenv('DEBUG') == 'true';

      chdir(getenv('CWD'));

      init_log($account);
      
      $this->url = getenv($url_key);
      $this->_init_db($db_key);
      $this->account_id = $this->get_account_id();          

      $acc = $this->get_full_account();
      $this->password = $acc->whatsapp_password;
      $this->nickname = $acc->name;

      if ($this->is_active()) {
        $this->wa = new WhatsProt($this->account, $this->nickname, $debug);
        $events = new Events($this, $this->wa, $url_key);
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
            
            $this->wa->pollMessage(true, "delivered");
            $this->get_jobs();

            $mid = microtime(true);
            $secs = intval($mid - $start);

            $diff = $timeout - $secs;
            if ($diff > 5) {
              sleep(3);
            }
            // l('Disconnect in: '.($timeout - $secs));
          }

          $end = microtime(true);
          $timediff = intval($end - $start);
          l('About to disconnect');
          $this->wa->disconnect(); 
          l('Disconnected');           
        }
        catch(ConnectionException $e) {          
          
          $msg = $e->getMessage();
          // Rollbar::report_message($this->account." Disconnected.", 'warning');
          l('Error occurred when trying to connect '.$msg);
                    
          if ($msg != "Connection Closed!")  
          {
            if (is_production()) {
              send_sms(getenv('ADMIN_TEL'), $this->account.' ('.$this->nickname.') has gone offline unexpectedly.');
            }
            // Any other error don't restart automatically
            exit(0);
          }
          else {
            // if connection closed, we should restart automatically
            $wait = intval(getenv('WAIT_TIMEOUT'));
            l('Going to wait for '.$wait);
            sleep($wait);

            exit(2);
          }
        }        
      }      
    }

    public function toggleConnection($status) {
      $this->connected = $status;
    }

    private function get_jobs() {
      $jobs = JobLog::all(array('sent' => false, 'account_id' => $this->account_id, 'pending' => false));
      
      if (count($jobs) > 0) {
        l("Num jobs ".count($jobs));

        foreach ($jobs as $job) {        
          $this->do_job($job);
          l($job->method);
        }  
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
      elseif ($job->method == "broadcast_Image") {
        $this->broadcast_image ($job);
      } 
      elseif ($job->method == "group_create") {
        $this->create_group($job);
      }
      elseif ($job->method == "group_end") {
        $this->end_group($job);
      }
      elseif ($job->method == "group_addParticipants") {
        $this->add_participants_to_group($job);
      }
      elseif ($job->method == "group_removeParticipants") {
        $this->remove_participants_from_group($job);
      }       
      elseif ($job->method == "profile_setStatus") {
        $this->set_status($job);
      }
      elseif ($job->method == "setProfilePicture") {
        $this->set_profile_picture($job);
      }
      elseif ($job->method == "sendContact") {
        $this->send_contact($job);
      }
      else {
        l('Job is '.$job->method);
      }
    }

    /**
     * End the group
     */
    private function end_group($job) {
      $this->wa->sendGroupsLeave(array($job->args));
      $job->sent = true;
      $job->save();
    }

    /**
     * Send vCard
     */
    private function send_contact($job) {      
      $to = $job->targets;
      $args = explode(",", $job->args);

      $first_name = $args[0];
      $last_name = $args[1];
      $tel = $args[2];
      $name = $first_name." ".$last_name;

      $card = new vCard();
      $card->set('data', array(
        'first_name' => $first_name,
        'last_name' => $last_name,
        'cell_tel' => $tel
      ));

      $job->whatsapp_message_id = $this->wa->sendVCard($to, $name, $card->show());
      $job->sent = true;
      $job->save();
    }

    /**
     * Set the profile picture
     */
    private function set_profile_picture($job) {
      $url = $this->url.$job->args;
      l('About to set profile picture to '.$url);

      $file_name = 'tmp/pp_'.$job->id.'_.jpg'; 

      if ($this->download($url, $file_name)) {
        $this->wa->sendSetProfilePicture($file_name);
        $job->sent = true;
        $job->save();
      }
    }

    /**
     * Set the profile status
     */
    private function set_status($job) {
      l('Going to set status '.$job->args);

      $this->wa->sendStatusUpdate($job->args);
      $job->sent = true;
      $job->save();
    }

    /**
     * Called to remove a participant from a group
     */
    private function remove_participants_from_group($job) {
      l('Going to remove '.$job->args);
      l('From group '.$job->targets);

      $members = explode(',', $job->args);

      $this->wa->sendGroupsParticipantsRemove($job->targets, $members);
      $job->sent = true;
      $job->save();
    }

    /**
     * Called to add participants to an existing group
     */
    private function add_participants_to_group($job) {
      $members = explode(',', $job->args);
      
      $this->wa->sendGroupsParticipantsAdd($job->targets, $members);    
      $job->sent = true;
      $job->save();
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
      
      $url = $this->url.'/groups/'.$group->id.'/activate_members';
      l('Posting to url '.$url);

      // post_data      
      $data = array('members' => $job->targets, 'account' => $this->account);
      post_data($url, $data);

      $job->sent = true;
      $job->save();
    }

    private function broadcast_image($job) {
      $asset = Asset::find_by_id($job->asset_id);
      $file_name = 'tmp/'.$asset->id.'_'.$asset->file_file_name;

      $url = $this->url.$asset->url;
      $broadcast = Broadcast::find_by_id($job->args);

      if ($this->download($url, $file_name)) {
        $targets = explode(',', $job->targets);
        $job->whatsapp_message_id = $this->wa->sendBroadcastImage($targets, $file_name, false, 0, "", $broadcast->message);
        $job->sent = true;
        $job->save();
      }
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

      $asset = Asset::find_by_id($asset_id);
      $file_name = 'tmp/'.$asset->id.'_'.$this->get_image_file_name($asset);

      $asset_url = $asset->url;
      if (!startsWith($asset->url, "http")) {
        $asset_url = $this->url.$asset->url;   
      } 

      $message = Message::find_by_id($job->message_id);
      $caption = $message->text;

      l('File name: '.$file_name);

      if ($this->download($asset_url, $file_name)) {        
        $job->whatsapp_message_id = $this->wa->sendMessageImage($job->targets, $file_name, false, 0, "", $caption);
        
        $job->sent = true;
        $job->save();

      }
    }

    private function get_image_file_name($asset) {
      $name = $asset->file_file_name;
      l('Name before: '.$name);
      l('Content type: '.$asset->file_content_type);
      if ($asset->file_content_type == "image/jpeg" && !endsWith($name, ".jpg")) {
        $name = $name.".jpg";
      } elseif ($asset->file_content_type == "image/png" && !endsWith($name, ".png")) {
        $nme = $name.".png";      
      }
      return $name;
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

    private function get_full_account() {
      return Account::find_by_phone_number($this->account);
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

    private function _init_db($key = 'DB') {
      $env = getenv('ENV');
      $db = getenv($key);

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