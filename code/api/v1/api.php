<?php
  require '../../assets/Slim-2.6.2/Slim/Slim.php';
  require 'db.php';
  require 'foodService.php';
  require 'medicineService.php';
  require 'assistantService.php';


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
