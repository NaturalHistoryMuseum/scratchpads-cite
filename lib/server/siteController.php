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
    // Just redirect to scratchpads.eu ; remove this to get functionality back.
    header('Location: http://scratchpads.eu');
    exit();
    // Gather all citations and theme them
    $citations = citationModel::find();
    $base_url = Application::$conf['base_url'];
    $template = Application::file('lib/site/index.tpl.php');
    ob_start();
    include $template;
    return new PageOutput(ob_get_clean());
  }
}
