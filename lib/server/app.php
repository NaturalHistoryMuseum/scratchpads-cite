<?php
/**
 * class Application
 *
 * This class contains general application-level data (such as configuration)
 * and methods.
 */
class Application {
  const LOG_NONE = 2;
  const LOG_ERROR = 1;
  const LOG_INFO = 0;

  const EXEC_TIMEOUT = 8192;

  static $conf = array(
    'log_level' => Application::LOG_NONE,
    'log_file' => 'log/log.txt'
  );

  static $root = '';
  static $remoteAddr = '';
  static $requestPath = array();
  static $request = array('');
  static $parameters = array();

  /**
   * initApp
   *
   * Generic initialisation, called directly from
   * more specific initialisation handlers.
   */
  static function initApp($root, $conf) {
    Application::$root = $root;
    $conf = array();
    require_once Application::file('conf.php');
    Application::$conf = array_merge(Application::$conf, $conf);
  }

  /**
   * initTestApp
   *
   * Call this to initialise the application when running tests
   */
  static function initTestApp($root, $conf) {
    Application::initApp($root, $conf);
    //Application::$conf['database'] = 'test/db.sqlite3';
    Application::$conf['log_file'] = 'test/log.txt';
    Application::$conf['log_level'] = Application::LOG_INFO;
  }

  /**
   * initCliApp
   *
   * Call this to initialise the application when running
   * cli commands
   *
   * Switches are added to parameters, either as TRUE or
   * with a value. Other parameters are set as the request.
   *
   * eg.
   * php cli.php -p one -x two yellow bicycle 
   * will cause Application::parameters to hold
   * array(
   *  'p' => 'one',
   *  'x' => 'two'
   * )
   * And Application::request to hold
   * array(
   *   'yellow', 'bicycle'
   * )
   */
  static function initCliApp($root, $conf) {
    Application::initApp($root, $conf);
    Application::log("Initialising Cli application");

    Application::$parameters = array();
    Application::$request = array();

    global $argv;
    $pos = 1;
    while ($pos < count($argv)) {
      if (preg_match('/^-(.+)$/', $argv[$pos])) {
        if ($pos + 1 < count($argv)) {
          Application::$parameters[$argv[$pos]] = $argv[$pos+1];
          $pos = $pos + 2;
        } else {
          Application::$parameters[$argv[$pos]] = TRUE;
          $pos++;
        }
      } else {
        Application::$request[] = $argv[$pos];
        $pos++;
      }
    }
  }

  /**
   * initApacheServerApp
   *
   * Call this to initialise the application when run through an
   * Apache server with php mod.
   */
  static function initApacheServerApp($root, $conf) {
    Application::initApp($root, $conf);

    Application::log("Initialising Apache server application.");

    Application::$remoteAddr = $_SERVER['REMOTE_ADDR'];
    Application::$parameters = $_REQUEST;
    Application::$requestPath = parse_url($_SERVER['REQUEST_URI']);
    Application::$request = array_filter(explode('/', Application::$requestPath['path']));
    if (empty(Application::$request)) {
      Application::$request = array('');
    }
  }

  /**
   * file
   *
   * Given a relative path, return the full path to the file in the current
   * application 
   */
  static function file($path) {
    if (preg_match('%^/%', $path)) {
      return $path;
    } else {
      return Application::$root . '/' . $path;
    }
  }

  /**
   * exec
   *
   * Runs a shell script and returns the output status
   */
  static function exec($command, $arguments, $timeout = 30) {
    // Create the command line
    $command = array(escapeshellcmd(Application::file($command)));
    foreach ($arguments as $index => $value) {
      if (is_string($index)) {
        if (preg_match('/=$/', $index)) {
          $command[] = escapeshellarg($index . $value);
        } else { 
          $command[] = escapeshellarg($index);
          $command[] = escapeshellarg($value);
        }
      } else {
        $command[] = escapeshellarg($value);
      }
    }
    $command_line = implode(' ', $command);
    // Run the process and wait
    Application::log("Running $command_line");
    $descriptors = array(
      0 => array("pipe", "r"), // stdin
      1 => array("pipe", "w"), // stdout
      2 => array("pipe", "w")  // stderr
    );
    $pipes = array();
    $proc = proc_open($command_line, $descriptors, $pipes);
    if ($proc === FALSE) {
      Application::log("There was an error attempting to run $command_line", Application::LOG_ERROR);
      return 1;
    }  
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);
    $status = proc_get_status($proc);
    $start = time();
    while ($status['running'] && time() < $start + $timeout) {
      sleep(1);
      $status = proc_get_status($proc);
    }
    // Read any output
    $output = array();
    $errors = array();
    while ($line = fgets($pipes[1])) {
      if (trim($line)) {
        $output[] = trim($line);
      }
    }
    while ($line = fgets($pipes[2])) {
      if (trim($line)) {
        $errors[] = trim($line);
      }
    }
    fclose($pipes[1]);
    fclose($pipes[2]);
    // Check for errors
    if ($status['running']) {
      $return = Application::EXEC_TIMEOUT;
      proc_terminate($proc, 9);
    } else {
      $return = $status['exitcode'];
    }
    if ($return > 0) {
      $message = "There was an error ";
      if ($status['running']) {
        $message .= "(The process timed out) ";
      } else {
        $message .= "(exit code $return) ";
      }
      $message .= "running the command $command_line.";
      if (count($output)) {
        $message .= "\nSTDOUT:\n" . implode("\n", $output) . "\n";
      } else {
        $message .= "There was no output on STDOUT.";
      }
      if (count($errors)) {
        $message .= "\nSTDERR:\n" . implode("\n", $errors) . "\n";
      } else {
        $message .= "There was no output on STDERR.";
      }
      Application::log($message, Application::LOG_ERROR);
    }
    return $return;
  }

  /**
   * log
   */
  static function log($message, $level = Application::LOG_INFO) {
    if ($level >= Application::$conf['log_level']) {
      if (!is_string($message)) {
        $message = serialize($message);
      }
      if ($level == Application::LOG_INFO) {
        $message = '[INFO ][' . date('c') . '] - ' . $message;
      } else {
        $message = '[ERROR][' . date('c') . '] - ' . $message;
      }
      file_put_contents(Application::file(Application::$conf['log_file']), $message . "\n", FILE_APPEND);
    }  
  }
}

