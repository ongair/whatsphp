<?php
  require 'lib/whatsapp/events/AllEvents.php';
  require 'models/Message.php';  
  use Pubnub\Pubnub;

  class Events extends AllEvents
  {
    public $acount;
    private $client;

    public function __construct($client, $wa, $url_key = 'URL') {
      parent::__construct($wa);
      $this->client = $client;
      $this->url = getenv($url_key);

      $this->pubnub = new Pubnub(
          getenv('PUB_KEY'),
          getenv('SUB_KEY'),
          "",
          false
        );

      $this->channel = getenv('PUB_CHANNEL')."_".$this->client->get_account();      
    }

    public $activeEvents = array(
      'onConnect',
      'onDisconnect',
      'onGetMessage',
      'onGetReceipt',
      'onGetGroupMessage',
      'onGetImage',
      'onGetGroupImage',
      'onGetVideo',
      'onGetGroupVideo',
      'onGetLocation',
      'onGroupsChatCreate',
      'onGroupsParticipantsAdd',
      'onGroupsParticipantsRemove',
      'onGroupisCreated'
    );

    public function onGroupisCreated( $me, $creator, $gid, $subject, $admin, $creation, $members = array()){
      l('Group created '.$subject.' id '.$gid);
      l('Members '.$members);

      $exists = Group::exists(array('jid' => $gid));
      if (!$exists) {
        $data = array('account' => $me, 'name' => $subject, 'jid' => $gid, 'group_type' => 'External', 'members' => $members);
        $this->post($this->url.'/groups', $data);  
      }
    }

    public function onGroupsChatCreate( $me, $gid ) 
    {
      l('Created group : '.$gid);
    }

    public function onGroupsParticipantsAdd($me, $groupId, $jid) 
    {
      l('Added participant '.$jid);
      l('To group '.$groupId);

      $data = array('account' => $me, 'groupJid' => $groupId, 'contact' => get_phone_number($jid), 'type' => 'add');
      $this->post($this->url.'/update_membership', $data);
    }

    public function onGroupsParticipantsRemove($me, $groupId, $jid)
    {
      l('Removed '.$jid.' from '.$groupId);
      $data = array('account' => $me, 'groupJid' => $groupId, 'contact' => get_phone_number($jid), 'type' => 'left');
      $this->post($this->url.'/update_membership', $data);
    }

    public function onGetMessage( $me, $from, $id, $type, $time, $name, $body )
    {
      l("Message from $name: $body");

      # check if the message exists in the db
      if (!$this->exists($id)) {
                

        $url = $this->url.'/messages';
        $data = array('account' => $me, 'message' => array( 'text' => $body, 'phone_number' => get_phone_number($from), 'message_type' => 'Text', 'whatsapp_message_id' => $id, 'name' => $name) );
        
        $this->post($url, $data);

        $notif = array('type' => 'text', 'phone_number' => get_phone_number($from), 'text' => $body, 'name' => $name);
        $this->send_realtime($notif);
      }      
    }

    public function onGetReceipt( $from, $id, $offline, $retry, $participant, $type )
    {
      l('Got '.$type.' receipt '.$id.' from '.$from);      
      
      $job = JobLog::find_by_whatsapp_message_id_and_account_id($id, $this->client->get_account_id());
      // l('Method '.$job->method);
      if ($job->method == "sendMessage" || $job->method == 'sendImage') {        

        $message = Message::find_by_id($job->message_id);
        $message->received = true;
        $message->receipt_timestamp = date('Y-m-d H:i:s');
        $message->save();

        if ($type == 'read')
        {
          $data = array('account' => $this->client->get_account(), 'receipt' => array( 'type' => 'read', 'message_id' => $message->id ));
          $this->post($this->url.'/receipt', $data);
        }

        // pubnub message delivered
      }
      elseif ($job->method == 'broadcast_Text' || $job->method == 'broadcast_Image') {        
        $data = array('account' => $this->client->get_account(), 'receipt' => array('message_id' => $id, 'phone_number' => get_phone_number($participant) ));
        $url = $this->url.'/broadcast_receipt';

        $this->post($url, $data);
      } 
    }

    public function onGetGroupMessage( $me, $from_group_jid, $from_user_jid, $id, $type, $time, $name, $body )
    {
      l("Got group message: $body - $from_group_jid");

      if (!$this->exists($id)) {

        $url = $this->url.'/receive_broadcast';
        $data = array('account' => $me, 'message' => array( 'text' => $body, 'group_jid' => $from_group_jid, 'message_type' => 'Text', 'whatsapp_message_id' => $id, 'name' => $name, 'jid' => get_phone_number($from_user_jid) ));
        
        $this->post($url, $data);        
      }
    }

    public function onGetGroupImage( $me, $from_group_jid, $from_user_jid, $id, $type, $time, $name, $size, $image_url, $file, $mimeType, $fileHash, $width, $height, $preview, $caption )
    {
      l("Got group image: $image_url");

      if (!$this->exists($id)) {
        $post_url = $this->url.'/upload';
        $data = array('account' => $me, 'message' => array('url' => $image_url, 'message_type' => 'Image', 'group_jid' => $from_group_jid, 'phone_number' => get_phone_number($from_user_jid), 'whatsapp_message_id' => $id, 'name' => $name ));
        $this->post($post_url, $data);
      }
    }

    public function onGetImage( $me, $from, $id, $type, $time, $name, $size, $image_url, $file, $mimeType, $fileHash, $width, $height, $preview, $caption )
    {
      $post_url = $this->url.'/upload';
      $data = array('account' => $me, 'message' => array('url' => $image_url, 'message_type' => 'Image', 'phone_number' => get_phone_number($from), 'whatsapp_message_id' => $id, 'name' => $name ));
      $this->post($post_url, $data);
    }

    public function onGetVideo( $me, $from, $id, $type, $time, $name, $video_url, $file, $size, $mimeType, $fileHash, $duration, $vcodec, $acodec, $preview, $caption )
    {
      $post_url = $this->url.'/upload';
      $data = array('account' => $me, 'message' => array('url' => $video_url, 'message_type' => 'Video', 'phone_number' => get_phone_number($from), 'whatsapp_message_id' => $id, 'name' => $name ));
      $this->post($post_url, $data);
    }

    public function onGetGroupVideo( $me, $from_group_jid, $from_user_jid, $id, $type, $time, $name, $url, $file, $size, $mimeType, $fileHash, $duration, $vcodec, $acodec, $preview, $caption )
    {
      $post_url = $this->url.'/upload';
      $data = array('account' => $me, 'message' => array('url' => $url, 'message_type' => 'Video', 'group_jid' => $from_group_jid, 'phone_number' => get_phone_number($from_user_jid), 'whatsapp_message_id' => $id, 'name' => $name ));
      $this->post($post_url, $data);
    }

    public function onGetLocation( $me, $from, $id, $type, $time, $name, $name, $longitude, $latitude, $url, $preview )
    {
      $post_url = $this->url.'/locations';
      $data = array('account' => $me, 'location' => array('latitude' => $latitude, 'longitude' => $longitude, 'preview' => '', 'phone_number' => get_phone_number($from), 'whatsapp_message_id' => $id, 'name' => $name));
      $this->post($post_url, $data);
    }

    public function onConnect($mynumber, $socket) {
      l("Connected");
      $this->client->toggleConnection(true);
    }

    public function onDisconnect($mynumber, $socket)
    {
      l("Disconnected");
      $this->client->toggleConnection(false);
    }

    private function post($url, $data) {
      $headers = array('Content-Type' => 'application/json', 'Accept' => 'application/json');
      Requests::post($url, $headers, json_encode($data));
    }

    private function is_broadcast_receipt($id) {
      return strpos($id, 'broadcast-') !== false;
    }

    private function exists($id) {      
      return Message::exists(array('whatsapp_message_id' => $id));
    }

    private function send_realtime($message) {
      $info = $this->pubnub->publish($this->channel, $message);
    }
  }
