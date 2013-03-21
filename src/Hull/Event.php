<?php 

class Hull_Event {

  public $payload;
  public $valid;
  public $nonce;
  public $timestamp;
  public $error;

  function Hull_Event($stream, $appSecret, $appId='') {
    try {
      $this->payload    = json_decode($stream);
      $appId            = strlen($appId) ? $appId : $_SERVER['HTTP_HULL_APP_ID'];
      if ($appId && $this->payload->appId && $appId !== $this->payload->appId) {
        throw new Exception('inconsistent app IDs');
      } elseif (!$appId) {
        $appId = $this->payload->appId;
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
      $this->valid      = $signature === hash_hmac("sha1", $data, $appSecret);
    } catch (Exception $e) {
      $this->error      = $e;
      $this->valid      = false;
    }
  }
}
