<?php

namespace SlimJson;

use Slim\Slim;

abstract class Config {
  const Debug = 'json.debug';
  const Status = 'json.status';
  const OverrideError = 'json.override_error';
  const OverrideNotFound = 'json.override_notfound';
  const Protect = 'json.protect';
  const Cors = 'json.cors';
  const ClearData = 'json.clear_data';
  const JsonEncodeOptions = 'json.json_encode_options';
}

class Middleware extends \Slim\Middleware {

   /**
   * @param array $config
   */
  function __construct($config = null, $app = null)
  {
    if($app == null){
      $app = Slim::getInstance();
    }
    $app->view(new View($app));

    $defaultConfig = array(
      'debug' => false, // Disable PrettyException middleware
      Config::Debug => false,
      Config::Status => false,
      Config::OverrideError => false,
      Config::OverrideNotFound => false,
      Config::Protect => false,
      Config::Cors => false,
      Config::ClearData => false,
      Config::JsonEncodeOptions => 0
    );
    if (\is_array($config)) {
      $config = array_merge($defaultConfig, $config);
    } else {
      $config = $defaultConfig;
    }
    $app->config($config);

    $overrideError = $app->config(Config::OverrideError);
    if ($overrideError) {
      $app->error(function (\Exception $e) use ($app, $overrideError) {

        if (\is_callable($overrideError)) {
          $func = $overrideError;
        }

        $return = array(
          'error' =>
            isset($func)
              ? \call_user_func($func, $e)
              : ($e->getCode() ? '' : '(#' . $e->getCode() . ') ') . $e->getMessage()
        );

        if ($app->config(Config::Debug)) {
          $return['_debug'] = array(
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace(),
          );
        }

        $app->render(500, $return);
      });
    }

    $overrideNotFound = $app->config(Config::OverrideNotFound);
    if ($overrideNotFound) {
      $app->notFound(function() use ($app, $overrideNotFound) {

        if (\is_callable($overrideNotFound)) {
          $func = $overrideNotFound;
        }

        $return = array(
          'error' =>
            isset($func)
              ? \call_user_func($func, $app->request())
              : '\'' . $app->request()->getPath() . '\' is not found.'
        );

        $app->render(404, $return);
      });
    }

    if ($app->config(Config::ClearData)) {
      $app->view->clear();
    }

    $app->hook('slim.before', function () use ($app) {
      $app->response()->header('Access-Control-Allow-Origin', "*");
      $test = $app->request()->headers();
      $cors = $app->config(Config::Cors);
      if ($cors) {
        
        if($cors === true){
            $allowOrigin = "*";
        }
        else if(is_callable($cors)){
            $allowOrigin = \call_user_func($cors, $app->request()->headers->get('Origin'));
        }
        else if(stristr($cors, "*"))
        {
              $origin = $app->request()->headers->get('Origin');
              $pattern = str_replace(".", "\.", $cors);
              $pattern = str_replace("*", "(.)+", $pattern);
              if(preg_match("/".$pattern."/", $origin)){
                  $allowOrigin = $origin;
              }else{
                  $allowOrigin = false;
              }  
        }else{
            $allowOrigin = $cors;
        }
        

        if($allowOrigin) {
          $app->response()->header('Access-Control-Allow-Origin', $allowOrigin);
        }
       
      }

      
    });
    
    $app->hook('slim.after.router', function () use ($app) {
        if($app->response()->header('Content-Type') === 'application/octet-stream') {
            $app->response()->headers->remove("Access-Control-Allow-Origin");
        }
        
        if ($app->config(Config::Protect)) {
          $app->response()->body('while(1);' . $app->response()->body());
        }
    });
  }
  
  
  public function call()
  {
    $this->next->call();
  }

  private function setConfigFunction($config, $func) {
    if (\is_callable($func) || \is_bool($func)) {
      $this->app->config($config, $func);
      return true;
    } else {
      return false;
    }
  }

  public function setErrorMessage($func)
  {
    return $this->setConfigFunction(Config::OverrideError, $func);
  }

  public function setNotFoundMessage($func)
  {
    return $this->setConfigFunction(Config::OverrideNotFound, $func);
  }

  static public function inject()
  {
    $args = \func_get_args();

    $app = Slim::getInstance();
    $config = null;
    foreach ($args as $arg) {
      if ($arg instanceof Slim) {
        $app = $arg;
      }

      if (\is_array($arg)) {
        $config = $arg;
      }
    }

    $app->add(new \SlimJson\Middleware($config));
  }

}
