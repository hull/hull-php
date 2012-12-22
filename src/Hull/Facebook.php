<?php 
class  Hull_Facebook {
  function  Hull_Facebook($config=array()){
    $this->config               = $config;
    $this->appId                = $config['appId'];
    $this->secret               = $config['secret'];
    $this->status               = $config['status'];
    $this->oauth                = $config['oauth'];
    $this->frictionlessRequests = $config['frictionlessRequests'];
 
    $this->likeGate             = $config['likeGate'];
    $this->likeGatePassed       = false;
 

    $this->pageId               = $config['pageId'];
    $this->currentPage          = array();
    $this->currentPageId        = $this->pageId;
    $this->currentPageUrl       = "";
    $this->client               = new Facebook($config);
    $this->signedRequest        = $this->client->getSignedRequest();
    $this->appData              = $this->parseAppData();
    $this->likeGatePassed       = $this->detectLikeGate();
    $this->currentPageUrl       = $this->detectCurrentPageUrl();
  }

  private function detectLikeGate(){
    if ($this->likeGate){
      if($this->signedRequest && $this->signedRequest['page']){
        return $this->signedRequest['page']['liked'];
      } else{
        return true;
      }
    }
  }

  private function detectPageId() {
    if($this->signedRequest && $this->signedRequest['page']){
      $this->setCurrentPageId($this->signedRequest['page']['id']);
    }
  }

  private function parseAppData() {
    $ad = array();
    if(isset($this->signedRequest['app_data'])){
      $params = explode('|', $this->signedRequest['app_data']);
      foreach ($params as $pair) {
        $kv = explode(':', $pair);
        $ad[$kv[0]]=$kv[1];
      }
    }
    return $ad;
  }

  private function setCurrentPageId($id='') {
    $this->currentPageId        = $id;
  }

  private function detectCurrentPageUrl(){
    $this->currentPage = $this->client->api("/" . $this->currentPageId, "GET");
    return $this->currentPage['link'];
  }

  public function redirectToPage(){
    global $_GET;
    $params = $_GET;
    $ad = array();
    foreach ($_GET as $key => $value) {
      $ad[]=$key.":".$value;
    }
    $p = implode('|', $ad);
    $l =  $this->currentPageUrl."?sk=app_".$this->appId;
    if(strlen($p)){
      $l .= "&app_data=".$p;
    }
    ?>
    <script type="text/javascript">
      window.top.location.href='<?php echo $l; ?>'
    </script>
    <?php
  }
}
