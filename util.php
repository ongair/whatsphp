<?php

  date_default_timezone_set('Africa/Nairobi');
  use Analog\Handler\File;

  function l($message)
  {
    echo date("H:i:s")." ".$message."\r\n";
    Analog::log ($message);
  }

  function d($object)
  {
    var_dump($object);
  }

  function split_jid($jid) {
    $split = explode("@", $jid);
    return $split[0];
  }

  function get_phone_number($jid)
  {
    return split_jid($jid);
  }

  function is_production() {
    return getenv('ENV') == 'production';
  }

  function is_development() {
    return getenv('ENV') == 'development';
  }

  function post_data($url, $data)
  {
    $headers = array('Content-Type' => 'application/json', 'Accept' => 'application/json');
    Requests::post($url, $headers, json_encode($data));
  }

  function init_log($account) {
    $env = getenv('ENV');
    $log_file = 'log/'.$account.'.'.$env.'.log';
    Analog::handler (Analog\Handler\File::init ($log_file));
  }

  function _init_db() {
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

  function is_running($response) {
    return !is_stopped($response);
  }

  function is_stopped($response) {  
    $pattern = "start/running";
    $pos = strpos($response, $pattern);

    return ($pos === false);
  }

  function contains_string($haystack, $needle) {
    return (strpos($haystack, $needle) !== false);
  }

  function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
  }

  function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
  }

  function list_services($dir = '/etc/init') {
    $output = shell_exec('ls '.$dir.'/whatsapp-*');
    $list = explode(PHP_EOL, $output);
    return $list;
  }

  function service_status($name, $status='') {
    if ($status == '')
      $status = shell_exec('service '.$name.' status');

    return startsWith($status, $name.' start/running');
  }

  function service_from_phone_number($phone_number, $grep='') {
    if ($grep == '')
      $grep = shell_exec('grep '.$phone_number.' /etc/init/whatsapp-*');
    preg_match("/\/etc\/init\/whatsapp-\S*.conf/", $grep, $matches);
    if (!empty($matches) && count($matches[0]) > 0)
      return $matches[0];
    else
      return NULL;
  }

  function service_running($phone_number) {    
    $response = shell_exec('ps aux | grep '.$phone_number);
    $lines = explode(PHP_EOL, $response);
    $first = $lines[0];
    preg_match("/\/var\/www\/whatsapp\/ongair.php\S* ".$phone_number."/", $first, $matches);
    return count($matches) > 0;
  }

  function service_name($conf) {
    preg_match("/whatsapp-.*.conf/", $conf, $matches);
    return strstr($matches[0], '.conf', true);
  }

  function getServiceName($name) {
    return str_replace(" ", "-", strtolower($name));
  }

  function send_email($to, $subject, $message) {
    $mandrill = new Mandrill(getenv('MANDRILL_KEY'));
    $msg = array(
        'text' => $message,
        'subject' => $subject,
        'from_email' => 'notifications@ongair.im',
        'from_name' => 'Ongair',
        'to' => array(
            array('email' => $to)
          )
      );
    $result = $mandrill->messages->send($msg);
  }

  function send_sms($to, $message) {
    $url = getenv('SMS_GATEWAY_URL');
    $channel_id = getenv('SMS_GATEWAY_CHANNEL_ID');
    $password =  getenv('SMS_GATEWAY_PASSWORD');
    $service_id = getenv('SMS_GATEWAY_SERVICE_ID');


    $xml = "<?xml version=\"1.0\"?>
      <methodCall>
        <methodName>EAPIGateway.SendSMS</methodName>
        <params>
          <param>
            <value>
              <struct>
                <member>
                  <name>Numbers</name>
                  <value>{$to}</value>
                </member>
                <member>
                  <name>SMSText</name>
                  <value>{$message}</value>
                </member>
                <member>
                  <name>Password</name>
                  <value>{$password}</value>
                </member>
                <member>
                  <name>Service</name>
                  <value>
                    <int>{$service_id}</int>
                  </value>
                </member>
                <member>
                  <name>Receipt</name>
                  <value>N</value>
                </member>
                <member>
                  <name>Channel</name>
                  <value>{$channel_id}</value>
                </member>
                <member>
                  <name>Priority</name>
                  <value>Urgent</value>
                </member>
                <member>
                  <name>MaxSegments</name>
                  <value>
                    <int>2</int>
                  </value>
                </member>
              </struct>
            </value>
          </param>
        </params>
      </methodCall>";

    $headers = array('content-type' => 'text/xml;charset=utf8');

    try
    {
      $response = Requests::post($url, $headers, $xml);
      return $response->success;
    }
    catch(Exception $e) {
      echo 'Caught exception: ',  $e->getMessage(), "\n";
      return false;
    }
  }
