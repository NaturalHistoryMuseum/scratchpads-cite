<?php
/**
 * This file defines the classes and
 * interfaces used to structure the 
 * server application
 *
 * - interface Controller: Controllers are used to
 *   manage a request
 * - class PageOut: The result of processing a page
 */

/**
 * interface Controller
 *
 * A Controller is used to handle a request to a given
 * path
 */
interface Controller {
  /**
   * prepareRequest 
   *
   * Prepare the request with the given path (as an array) and
   * parameters (as a map).
   *
   * Any validation should be performed at this stage, and an
   * exception should be thrown on error.
   */
  public function prepareRequest($path, $parameters);

  /**
   * processRequest
   *
   * Process the current request, and
   * return a PageOutput object
   */
  public function processRequest();
}

/**
 * class Model
 *
 * This contains both a number of static methods used to managed models,
 * and represents a base class for models to implement.
 *
 */
class Model {
  static $models = array();

  /**
   * Model::register
   *
   * This is used to register a new model
   */
  static function register($class, $key, $indexes = array()) {
    // Check we have required info
    if (isset(Model::$models[$class])) {
      throw new Exception("Model $class already exists");
    }
    if (!class_exists($class) || !property_exists($class, $key)) {
      throw new Exception("Class $class does not exist or does not have field $key");
    }
    foreach ($indexes as $i) {
      if (!property_exists($class, $i)) {
        throw new Exception("Class $class does not have a field $i for indexing");
      }
    }
    // Store model info
    $indexes[] = $key;
    $indexes = array_unique($indexes);
    Model::$models[$class] = array(
      'class' => $class,
      'keyField' => $key,
      'indexes' => $indexes
    );
    $tpl = Application::file('lib/site/' . $class . '.tpl.php');
    if (file_exists($tpl)) {
      Model::$models[$class]['template'] = $tpl;
    }
  }

  /**
   * Model::modelInfo
   *
   * Given either a model object or a model name, return
   * the info array or raise an exception
   */
  private static function modelInfo($model) {
    if (is_object($model)) {
      $model_name = get_class($model);
    } else {
      $model_name = $model;
    }
    if (empty(Model::$models[$model_name])) {
      throw new Exception("Unknown model $model_name");
    }
    return Model::$models[$model_name];
  }
  
  /**
   * static database
   *
   * Open/Create the database, and return an MySQLi object
   */
  private static function database() {
    static $init = FALSE;
    if (empty(Application::$conf['database'])) {
      throw new Exception("No database configured");
    }
    $cnx = Application::$conf['database'];
    $db = new mysqli($cnx['host'], $cnx['user'], $cnx['password'], $cnx['database']);
    if ($db->connect_errno > 0) {
      throw new Exception("Error while connecting to the database: $db->connect_error");
    }
    if (!$init) {
      $init = TRUE;
      // Create the schema if it doesn't exist 
      $test = $db->query("SHOW TABLES LIKE 'key'");
      if ($test->num_rows == 0) { 
        $schema = array(
          'modelkey' => array(
            'keyid INTEGER AUTO_INCREMENT PRIMARY KEY',
            'classname VARCHAR(255)',
            'keyvalue BLOB'
         ),
         'model' => array(
           'keyid INTEGER PRIMARY KEY',
           'model BLOB'
         ),
         'fieldindex' => array(
           'keyid INTEGER NOT NULL',
           'field VARCHAR(255) NOT NULL',
           'value BLOB'
         )
        );
      }
      foreach ($schema as $table => $def) {
        $db->query("CREATE TABLE $table (" . implode(',', $def) . ")");
      }
    }
    return $db;
  }

  /**
   * Model::find
   *
   * This is used to find instances of a model from the database
   */
  static function find($key = NULL, $model_name = NULL) {
    if ($model_name === NULL) {
      $model_name = get_called_class();
    }
    $info = Model::modelInfo($model_name);
    $db = Model::database();

    $single = FALSE;
    if ($key !== NULL && !is_array($key)) {
      $key = array($info['keyField'] => $key);
      $single = TRUE;
    }
    $join = array();
    $where = array();
    if (is_array($key)) {
      $count = 1;
      foreach ($key as $field => $value) {
        $field = "'" . $db->real_escape_string($field) . "'";
        if (!is_array($value)) {
          $value = array('=', $value);
        }
        $value = $value[0] . "'" . $db->real_escape_string($value[1]) . "'";
        $join[] = "INNER JOIN fieldindex index_$count ON index_$count.keyid = model.keyid";
        $where[] = "index_$count.field = $field AND index_$count.value $value";
      }
    }
    $join[] = "INNER JOIN modelkey ON modelkey.keyid = model.keyid";
    $where[] = "modelkey.classname = '" . $db->real_escape_string($info['class']) . "'";
    $query = "
      SELECT model
        FROM model
    " . implode(' ', $join) . "
      WHERE
    " . implode(' AND ', $where) . "
      ORDER BY model.keyid DESC
    ";
    
    $result = $db->query($query);
    if (!$result) {
      throw new Exception("There was an error running $query");
    }
    $models = array();
    while ($row = $result->fetch_assoc()) {
      $models[] = unserialize($row['model']); 
    }
    $db->close();
    if ($single) {
      return reset($models);
    } else {
      return $models;
    }
  }


  /**
   * save
   *
   * Model method that calls Model::modelSave()
   */
  public function save() {
    $info = Model::modelInfo($this);
    $db = Model::database();
    // Get the integer id for this model's key
    $key = $db->real_escape_string($this->{$info['keyField']});
    $class = $db->real_escape_string($info['class']);
    $r = $db->query("SELECT keyid FROM modelkey WHERE keyvalue='$key' AND classname='$class'");
    if (!$r) {
      throw new Exception("Error running query: " . $db->error);
    }
    if ($r->num_rows == 0) {
      $db->query("INSERT INTO modelkey(classname, keyvalue) VALUES('$class', '$key')");
      $id = $db->insert_id;
    } else {
      $row = $r->fetch_assoc();
      $id = $row['keyid'];
      $db->query("DELETE FROM model WHERE keyid='$id'");
      $db->query("DELETE FROM fieldindex WHERE keyid='$id'");
    }
    $db->query("INSERT INTO model VALUES($id, '" . $db->real_escape_string(serialize($this)) . "')");
    foreach ($info['indexes'] as $field) {
      $db->query("INSERT INTO fieldindex VALUES($id, '" . $db->real_escape_string($field) . "', '" . $db->real_escape_string($this->{$field}) . "')");
    }
    $db->close();
  }

  /**
   * render
   *
   * Model method to render
   */
  public function render() {
    $info = Model::modelInfo($this);
    if (empty($info['template'])) {
      ob_start();
      print_r($this);
      return ob_get_clean();
    } else {
      extract((array)$this);
      ob_start();
      include($info['template']);
      return ob_get_clean();
    }
  }
}

/**
 * class PageOutput
 *
 * Class used to represent the output of a page.
 */
class PageOutput {
  const HTML = 'html';
  const JSON = 'json';

  /**
   * Create a new PageOutput from the given data and,
   * optionalliy, a type (of PageOutput::TYPE_ constantants).
   * If no type is given, it will be deduced from the type
   * of $data:
   * - If a string, type will be assumed to be html ;
   * - If an array, type will be assumbed to be json
   */
  public function __construct($data, $type = NULL) {
    $this->data = $data;
    if ($type !== NULL) {
      $this->type = $type;
    } else if (is_array($data) || is_object($data)) {
      $this->type = PageOutput::JSON;
    } else if (is_string($data)) {
      $this->type = PageOutput::HTML;
    } else {
      throw new Exception("PageOutput: unkown data type");
    }
  } 

  /**
   * render 
   *
   * Render the output. If $to_browser is TRUE, then
   * this will generate HTTP headers and output the
   * page. If $to_browser is FALSE, then this will return
   * the rendered output.
   */
  public function render($to_browser = TRUE) {
    if ($this->type == PageOutput::JSON) {
      $flat = json_encode($this->data);
    } else {
      $flat = $this->data;
    }
    if (!$to_browser) {
      return $flat;
    }
    if ($this->type == PageOutput::JSON) {
      header('Content-type: application/json');
    } else if ($this->type == PageOutput::HTML) {
      header('Content-type: text/html');
    } else {
      header('Content-type: text/plain');
    }
    echo $flat;
  }

  /**
   * type
   *
   * Return the type of this PageOutput
   */
  public function type() {
    return $this->type;
  }

  /**
   * data
   *
   * Return the data of this PageOutput
   */
  public function data() {
    return $this->data;
  }
}
  
