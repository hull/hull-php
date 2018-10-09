<?php

class Hull_Event {

  public $payload;
  public $valid;
  public $nonce;
  public $timestamp;
  public $error;

  function __construct($stream, $app_secret, $app_id='') {
    try {
      $this->payload    = json_decode($stream);
      $app_id            = strlen($app_id) ? $app_id : $_SERVER['HTTP_HULL_APP_ID'];
      if ($app_id && $this->payload->app_id && $app_id !== $this->payload->app_id) {
        throw new Exception('inconsistent app IDs');
      } elseif (!$app_id) {
        $app_id = $this->payload->app_id;
      }

      $signature        = $_SERVER['HTTP_HULL_SIGNATURE'];
      $sig              = explode('.', $signature);
      if (!$sig) {
        throw new Exception ('Message not signed');
      }
      $this->timestamp  = $sig[0];
      $this->nonce      = $sig[1];
      $hash             = $sig[2];
      $data             = implode("-", array($this->timestamp, $this->nonce, $stream));
      $this->valid      = $signature === hash_hmac("sha1", $data, $app_secret);
    } catch (Exception $e) {
      $this->error      = $e;
      $this->valid      = false;
    }
  }
}
