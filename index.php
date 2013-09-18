<?php

/* Bootstrap */
$root = realpath(str_replace('index.php', '', __FILE__));
require_once $root . '/lib/server/app.php';
Application::initApacheServerApp($root, 'conf.php');

function __autoload($class) {
  if (!class_exists('Application')) {
    throw new Exception("__autoload requires Application class to be initialised");
  }
  $file = Application::file('lib/server/' . $class . '.php');
  if (file_exists($file)) {
    require_once($file);
  } else {
    throw new Exception("Unknown class $class");
  }
}

require_once Application::file('lib/server/mvc.php');
 
/* Routing */
$controller = NULL;
switch(reset(Application::$request)) {
  case '':
    Application::log('Routing: home');
    $controller = new siteController();
  break;
  case 'generate':
    Application::log('Routing: Generate PDF');
    $controller = new generateController();
    break;
  case 'preview':
    Application::log('Rounting: Generate preview');
    $controller = new generateController();
    break;
  default:
    Application::log('Routing error, unknown request: ' . reset(Application::$request), Application::LOG_ERROR);
    exit();
    break;
}

/* Execute controller */
if ($controller) {
  try {
    Application::log("Preparing request");
    $controller->prepareRequest(Application::$request, Application::$parameters);
  } catch (Exception $e) {
    Application::log("Preparing request failed: " . $e->getMessage(), Application::LOG_ERROR);
    $output = new PageOutput(array(
      'status' => 0,
      'error' => $e->getMessage()
    ));
    $output->render();
    exit();
  }
  Application::log("Processing request");
  $output = $controller->processRequest();
  Application::log("Rendering request result");
  $output->render();
  exit();
}
