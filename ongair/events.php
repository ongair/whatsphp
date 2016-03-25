<?php

  use Pubnub\Pubnub;

  class Events
  {
    // the Ongair client
    private $client;

    // The events to listen for
    private $activeEvents = array(
      'onConnect',
      'onDisconnect',
      'onLoginSuccess',
      'onLoginFailed',
      'onGetMessage',
      'onMessageReceivedClient',
      'onGetSyncResult',
      // 'onGetImage',
      // 'onGetGroupMessage',
      // 'onGetGroupImage',
      // 'onGetVideo',
      // 'onGetGroupVideo',
      // 'onGetAudio',
      // 'onGetLocation',
      // 'onGroupsChatCreate',
      // 'onGroupsParticipantsAdd',
      // 'onGroupsParticipantsRemove',
      // 'onGroupisCreated',
    );

    // Constructor
    public function __construct($client) {
      $this->client = $client;      
      $this->setEventsToListenFor($this->activeEvents);

      // setup the realtime
      $this->pubnub = new Pubnub(getenv('pub_key'), getenv('sub_key'), "", false);
      $this->channel = getenv('pub_channel')."_".$this->client->getAccount()->phone_number;        
    }

    // On successfully connected
    public function onConnect($me, $socket) {
      l("Connected");
      $this->client->toggleConnection(true);
    }

    // On disconnected
    public function onDisconnect($me, $socket)
    {
      l("Disconnected");
      $this->client->toggleConnection(false);
    }

    // On login failed
    public function onLoginFailed($me, $data) {
      l('Login failed '.$data);
    }

    // On image received
    public function onGetImage( $me, $from, $id, $type, $time, $name, $size, $image_url, $file, $mimeType, $fileHash, $width, $height, $preview, $caption )
    {
      l("Image received from $from url is $image_url");
      $post_url = '/upload';
      $data = array('message' => array('url' => $image_url, 'message_type' => 'Image', 'phone_number' => get_phone_number($from), 'whatsapp_message_id' => $id, 'name' => $name ));

      $this->client->post($post_url, $data);
    }

    // When a text message has been received
    public function onGetMessage( $me, $from, $id, $type, $time, $name, $body )
    {
      l("Message from $name: $body");
    
      $phone_number = get_phone_number($from);

      $data = array('message' => array('text' => $body, 'phone_number' => $phone_number, 'message_type' => 'Text', 'whatsapp_message_id' => $id, 'name' => $name ));
      $this->client->post('/messages', $data);

      $notification = array('type' => 'text', 'phone_number' => $phone_number , 'text' => $body, 'name' => $name);
      $this->sendRealtime($notification);
    }

    // When a message is received
    public function onMessageReceivedClient($me, $from, $id, $type, $time, $participant) {
      l("Message received client $type - $from");

      $account = $this->client->getAccount();
      $job = JobLog::find_by_whatsapp_message_id_and_account_id($id, $account->id);

      if ($job != NULL) {
        if ($job->method == "sendMessage" || $job->method == 'sendImage') {
          // single message
          $message = Message::find_by_id($job->message_id);
          if ($message != NULL) {
            $message->received = true;
            $message->receipt_timestamp = date('Y-m-d H:i:s');
            $message->save();

            $type = $type != "" ? $type : 'delivered';
            
            $data = array('receipt' => array( 'type' => $type, 'message_id' => $message->id ));
            $this->client->post('/receipt', $data);            
          }       
        }
      } 
    }

    // on sync result
    public function onGetSyncResult($result) {
      l("Received result from sync");

      $existing = array();
      foreach ($result->existing as $number) {
        array_push($existing, get_phone_number($number));
      }

      $data = array('registered' => $existing, 'unregistered' => $result->nonExisting );

      $this->client->post('/contacts/sync', $data);
    }

    // On login success
    public function onLoginSuccess($me, $kind, $status, $creation, $expiration) {
      l('Logged in '.$status);      
      $this->client->post('/status', array('status' => 1, 'message' => 'Connected' ));
    }

    /**
     * Register the events you want to listen for.
     *
     * @param array $eventList
     *
     * @return AllEvents
     */
    protected function setEventsToListenFor(array $eventList)
    {
      $this->eventsToListenFor = $eventList;
      return $this->startListening();
    }

    /**
     * Binds the requested events to the event manager.
     *
     * @return $this
     */
    protected function startListening()
    {
      foreach ($this->eventsToListenFor as $event) {
        if (is_callable([$this, $event])) {
          $this->client->getClient()->eventManager()->bind($event, [$this, $event]);
        }
      }

      return $this;
    }

    // sendRealtime to pubnub
    private function sendRealtime($message) {
      $info = $this->pubnub->publish($this->channel, $message);
    }
  }
