<?php
  require 'lib/whatsapp/events/AllEvents.php';
  require 'models/Message.php';  

  class Events extends AllEvents
  {
    public $acount;
    private $client;

    public function __construct($client, $wa) {
      parent::__construct($wa);
      $this->client = $client;
      $this->url = getenv('URL');
    }

    public $activeEvents = array(
      'onConnect',
      'onDisconnect',
      'onGetMessage',
      'onGetReceipt',
      'onGetGroupMessage',
      'onGetImage'
    );

    public function onGetMessage( $me, $from, $id, $type, $time, $name, $body )
    {
      l("Message from $name: $body");

      # check if the message exists in the db
      if (!$this->exists($id)) {
                

        $url = $this->url.'/messages';
        $data = array('account' => $me, 'message' => array( 'text' => $body, 'phone_number' => get_phone_number($from), 'message_type' => 'Text', 'whatsapp_message_id' => $id, 'name' => $name) );
        
        $this->post($url, $data);
      }      
    }

    public function onGetReceipt( $from, $id, $offline, $retry )
    {
      l("Got receipt ".$id);
      
      $job = JobLog::find_by_whatsapp_message_id_and_account_id($id, $this->client->get_account_id());
      if ($job->method == "sendMessage") {        

        $message = Message::find_by_id($job->message_id);
        $message->received = true;
        $message->receipt_timestamp = date('Y-m-d H:i:s');
        $message->save();

        // pubnub message delivered
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

    public function onGetImage( $me, $from, $id, $type, $time, $name, $size, $image_url, $file, $mimeType, $fileHash, $width, $height, $preview, $caption )
    {
      l('Got image : '.$url.' '.$me);      

      $post_url = $this->url.'/upload';
      $data = array('account' => $me, 'message' => array('url' => $image_url, 'message_type' => 'Image', 'phone_number' => get_phone_number($from), 'whatsapp_message_id' => $id, 'name' => $name ));
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

    private function exists($id) {      
      return Message::exists(array('whatsapp_message_id' => $id));
    }
  }
