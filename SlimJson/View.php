<?php

namespace SlimJson;

use Slim\Slim;

class View extends \Slim\View {

  /**
   * @param int|string $status
   * @param array|null $data
   * @return void
   */
  public function render($status, $data = null)
  {
    $app = Slim::getInstance();
    $response = array_merge($this->all(), (array) $data);

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
    return json_encode($response);
  }
}