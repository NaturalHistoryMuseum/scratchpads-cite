<?php

class siteController implements Controller {
  /**
   * Read and validate our parameters
   */
  public function prepareRequest($path, $parameters) {
  }

  /**
   * processRequest
   */
  public function processRequest() {
    // Gather all citations and theme them
    $citations = citationModel::find();
    $base_url = Application::$conf['base_url'];
    $template = Application::file('lib/site/index.tpl.php');
    ob_start();
    include $template;
    return new PageOutput(ob_get_clean());
  }
}
