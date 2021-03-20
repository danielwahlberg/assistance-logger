<?php
  require '../../assets/Slim-2.6.2/Slim/Slim.php';
  require 'db.php';
  require 'foodService.php';
  require 'medicineService.php';
  require 'assistantService.php';
  require 'eventService.php';
  require 'newsService.php';
  require 'securityService.php';

  // server should keep session data for AT LEAST 10 hours
  ini_set('session.gc_maxlifetime', 36000);

  // each client should remember their session id for EXACTLY 10 hours
  session_set_cookie_params(36000);
  session_start();

  \Slim\Slim::registerAutoloader();

  $app = new \Slim\Slim(
    array(
      'mode' => 'development',
      'debug' => true,
      'log.level' => \Slim\Log::DEBUG
    )
  );
  $app->add(new \Slim\Middleware\ContentTypes()); // Make sure JSON encoded post variables are parsed by Slim
  $app->response->headers->set('Content-Type', 'application/json');
  $log = $app->getLog();

  require 'routes.php';

  $app->run();
?>
