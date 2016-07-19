<?php

  use Analog\Handler\File;
  use Aws\S3\S3Client;
  use Aws\Credentials\Credentials;
  use Aws\S3\MultipartUploader;
  use Aws\Exception\MultipartUploadException; 

  function l($message)
  {
    echo date("H:i:s")." ".$message."\r\n";
    Analog::log ($message);
  }

  function info($message) {    
    echo date("H:i:s")." ".$message."\r\n";
    $logger = OngairLogger::getLogger();    
    $logger->info($message);
  }

  function err($message, $ex) {
    echo date("H:i:s")." ".$message."\r\n";
    $logger = OngairLogger::getLogger();
    $logger->error($message, [$ex]);
  }

  function dbg($message) {
    echo date("H:i:s")." ".$message."\r\n";
  }

  function split_jid($jid) {
    $split = explode("@", $jid);
    return $split[0];
  }

  function get_phone_number($jid)
  {
    return split_jid($jid);
  }

  function is_prod() {
    return getenv('env') == 'production';
  }

  function upload_file($folder, $name, $file)
  {
    $secret = getenv('aws_secret_access_key');
    $key = getenv('aws_key_id');
    $bucket = getenv('aws_bucket');
    
    $credentials = new Credentials($key, $secret);
    $client = new S3Client([ 'version' => 'latest', 'region' => 'ap-southeast-1', 'credentials' => $credentials ]);

    $uploader = new MultipartUploader($client, $file, [
      'bucket' => $bucket,
      'key'    => $folder.'/'.$name,
    ]);

    try {
      $result = $uploader->upload();
      return $result['ObjectURL'];
    } 
    catch (MultipartUploadException $e) {
      return null;
    }
  }

  function get_extension($mime_type) {
    switch ($mime_type) {
      case 'image/jpeg':
        return ".jpg";
      case 'image/png':
        return ".png";
      default:
        return "";
    }
  }

  function download($url) {
    $rand = substr(uniqid('', true), -8);
    $filename = "/tmp/$rand";

    try
    {
      file_put_contents($filename, file_get_contents($url));
      $mime = getimagesize($filename)['mime'];
      $extension = get_extension($mime);
      $path = $filename.$extension;
      rename($filename, $path);
      return $path;
    }
    catch(Exception $e) {
      return null;
    }
  }

  function notify_slack($msg) {
    $slack_token = getenv('slack_token');
    $url = "https://ongair.slack.com/services/hooks/incoming-webhook?token=$slack_token";
    try {
      $payload = array('channel' => "#activation", 'username' => 'webhookbot', 'icon_emoji' => ':ghost:', 'text' => $msg);      
      $headers = array('Content-Type' => 'application/json', 'Accept' => 'application/json');

      if (is_prod())
        Requests::post($url, $headers, json_encode($payload), array('timeout' => 5000));
    }
    catch(Exception $ex) {
      err("Error with posting to slack: ".$ex->getMessage(), $ex);
    }
  }