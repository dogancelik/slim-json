<?php

namespace SlimJson;

use Slim\Slim;

class View extends \Slim\View {

  private $app;
  
  public function __construct(\Slim\Slim $app) {
        $this->app = $app;
        parent::__construct();
  }
  
  /**
   * @param int|string $status
   * @param array|null $data
   * @return void
   */
  public function render($status, $data = null)
  {
    $app = $this->app;
    $response = $this->all();

    $status = \intval($status);
    $app->response()->status($status);
    if ($app->config(Config::Status)) {
      $response['_status'] = $status;
    }

    if (isset($response['flash']) && \is_object($response['flash'])) {
      $flash = $this->data->flash->getMessages();
      if (count($flash)) {
        $response['flash'] = $flash;
      } else {
        unset($response['flash']);
      }
    }

    $app->response()->header('Content-Type', 'application/json');
    $body = json_encode($response, $app->config(Config::JsonEncodeOptions));
    if($status == 404){
        return $body;
    }else{
        $app->response()->body($body);
    }
 }
}
