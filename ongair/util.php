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

  function init_log($account) {
    $env = getenv('env');
    $log_file = '/var/log/ongair.'.$account.'.'.$env.'.log';
    Analog::handler (Analog\Handler\File::init ($log_file));
  }

  function split_jid($jid) {
    $split = explode("@", $jid);
    return $split[0];
  }

  function get_phone_number($jid)
  {
    return split_jid($jid);
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