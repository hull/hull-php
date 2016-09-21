<?php

require_once 'Connection.php';
require_once 'Cache.php';
require_once 'Event.php';

class Hull_Client {

  static $version = "0.1.0";
  static $dateFormat = "Y-m-d H:i:s O";

  public $debug = false;

  static $configKeys = array('host', 'appId', 'appSecret');

  static $defaultConfig = array('host' => '', 'debug' => false);

  function Hull_Client($o_config=array()){

    $config = self::parseConfig($o_config['hull']);

    $this->config = $config;

    $this->host        = $config['host'];
    $this->appId       = $config['appId'];
    $this->appSecret   = $config['appSecret'];
    $this->userId      = false;
    if (isset($config['userId'])) {
      $this->userId      = $config['userId'];
    }

    $this->connection  = new Hull_Connection($config);

    if (isset($config['noHttpCache'])){
     $this->noHttpCache = $config['noHttpCache'];
    }

    if (isset($config['verbose'])){
      $this->verbose = (bool)$config['verbose'];
    } else {
      $this->verbose = false;
    }

    if (isset($config['debug']) && $config['debug']=='true') {
      $this->debug = (bool)$config['debug'];
      $this->debug_options = array();
    }
  }

  private static function decamelize($camel,$splitter="_") {
    $camel=preg_replace('/(?!^)[[:upper:]][[:lower:]]/', '$0', preg_replace('/(?!^)[[:upper:]]+/', $splitter.'$0', $camel));
    return strtolower($camel);
  }

  private static function parseConfig($config=array()) {

    foreach (self::$configKeys as $key) {
      $val = NULL;
      $envKey = "HULL_" . strtoupper(self::decamelize($key));

      if (isset($config[$key])) {
        $val = $config[$key];
      } elseif (getenv($envKey)) {
        $val = getenv($envKey);
      } elseif (isset(self::$defaultConfig[$key])) {
        $val = self::$defaultConfig[$key];
      }

      $config[$key] = $val;
    }
    return $config;
  }

  private function buildUserToken($claims) {
    if (isset($claims['nbf'])) {
      $claims['nbf'] = (int) $claims['nbf'];
    }

    if (isset($claims['exp'])) {
      $claims['exp'] = (int) $claims['exp'];
    }

    return JWT::encode(array_merge($claims, array(
      'iss' => $this->appId,
      'iat' => time()
    )), $this->appSecret);
  }

  public function userToken($identifier, $claims = array()) {
    if (is_string($identifier)) {
      $claims['sub'] = $identifier;
    } else if (
      !is_array($identifier) ||
      (!isset($identifier['email']) && !isset($identifier['external_id']) && !isset($identifier['guest_id']))) {
      throw new Exception('you need to pass a User hash with an `email` or `external_id` or `guest_id` field');
    } else {
      $claims['io.hull.user'] = $identifier;
    }
    return $this->buildUserToken($claims);
  }

  public function currentUserIdFromAccessToken() {
    $accessToken = false;
    if (isset($_REQUEST['access_token'])) {
      $accessToken = $_REQUEST['access_token'];
    }
    if (!$accessToken && isset($_SERVER['HTTP_HULL_ACCESS_TOKEN'])) {
      $accessToken = $_SERVER['HTTP_HULL_ACCESS_TOKEN'];
    }
    if ($accessToken) {
      $claims = JWT::decode($accessToken, $this->appSecret);
      if ($claims) {
        return $claims->sub;
      }
    } else {
      return false;
    }
  }

  public function currentUserId() {
    $userId = false;
    $userId = $this->currentUserIdFromCookie();
    if (!$userId) {
      $userId = $this->currentUserIdFromAccessToken();
    }
    return $userId;
  }


  public function currentUserIdFromCookie() {
    $rawSignature = false;
    $cookieName = 'hull_' . $this->appId;
    $rawSignature = isset($_COOKIE[$cookieName]) ? $_COOKIE[$cookieName] : false;

    if (!$rawSignature && isset($_SERVER['HTTP_HULL_USER_SIG'])) {
      $rawSignature = $_SERVER['HTTP_HULL_USER_SIG'];
    }

    if (!$rawSignature) {
      return;
    }

    $signedCookie = json_decode(base64_decode($rawSignature), true);
    $userId = $signedCookie['Hull-User-Id'];
    $sig    = explode(".", $signedCookie['Hull-User-Sig']);
    $time   = $sig[0];
    $signature = $sig[1];
    $data = $time . '-' . $userId;
    $check = hash_hmac("sha1", $data, $this->appSecret);
    if ($check == $signature) {
      return $userId;
    }
  }

  // Current User
  public function asUser($userId) {
    $userConfig = array_merge(array('userId' => $userId), $this->config);
    return new self(array('hull' => $userConfig));
  }

  public function asCurrentUser() {
    $userId = $this->currentUserId();
    if ($userId) {
      return $this->asUser($userId);
    } else {
      throw new Exception("No 'current user' logged in.");
    }
  }

  // View Helpers
  public function imageUrl($id, $size="small") {
    $url = $this->host . "/img/" . $id . "/" . $size;
    if (!preg_match('/^https?/', $url)) {
      $url = '//' . $url;
    }
    //Assets have their own subdomain
    return str_replace('//', '//assets.', $url);
  }

  public function userHash($userInfos) {
    if (!is_array($userInfos)) {
      return false;
    }

    if (!isset($userInfos['hull_user_id']) && !isset($userInfos['email'])) {
      return false;
    }
    $message = base64_encode(json_encode($userInfos));
    $timestamp = time();
    $signature = hash_hmac("sha1", "$message $timestamp", $this->appSecret);
    return "$message $signature $timestamp";
  }

  public function getEvent() {
    return new Hull_Event(file_get_contents('php://input'), $this->appSecret, $this->appId);
  }

  // HTTP Plumbing...

  public function get($path, $params=array(), $options=array()) {
    return $this->_exec("GET", $path, $params, $options);
  }

  public function post($path, $data=array(), $options=array()) {
    return $this->_exec("POST", $path, $data, $options);
  }

  public function put($path, $data=array(), $options=array()) {
    return $this->_exec("PUT", $path, $data, $options);
  }

  public function delete($path, $data=array(), $options=array()) {
    return $this->_exec("DELETE", $path, $data, $options);
  }

  private function _exec($method, $path, $params=array(), $options=array()) {
    if (isset($options["headers"])) {
      $headers = $options['headers'];
    } else {
      $headers = array();
    }

    $res = $this->connection->exec(strtoupper($method), $path, $params, $headers);

    if (isset($options['raw'])) {
      return $res;
    } else {
      return $res['body'];
    }
  }
}
