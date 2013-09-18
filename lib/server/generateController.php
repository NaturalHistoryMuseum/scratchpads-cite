<?php

/**
 * This controller is called to generate a new PDF
 * of a page
 *
 * Expected parameters are:
 * - url: The URL to quote ;
 * - title: The title of the page ;
 * - date: The date of the page in %Y-%m-%d format ;
 * - authors: The authors of the page
 * - post: The post data to pass in when generating the page.
 *
 * This page will return a json structure describing:
 *
 * status: 1 on success, 0 otherwise
 * error: If status is 0, an error message
 * In generate mode:
 *   pdf: Full URL to the generated PDF file ;
 *   bibtex: Full URL to the generated bibtext file
 * In preview mode:
 *   png: Full URL to the generated PNG file ;
 */
class generateController implements Controller {
    
  /**
   * Read and validate our parameters
   */
  public function prepareRequest($path, $parameters) {
    $errors = array();

    // Check this comes from an allowed source
    if (!in_array(Application::$remoteAddr, Application::$conf['allowed_ips'])) {
      throw new Exception("Not allowed");
    } 

    // Check we have required configuration settings to run this
    if (empty(Application::$conf['preview_folder']) ||
        !is_dir(Application::$conf['preview_folder'])) {
      $errors[] = "Cannot find preview folder";
    }
    if (empty(Application::$conf['set_pdf_meta'])) {
      $errors[] = "No command to set PDF meta data available";
    }
    if (empty(Application::$conf['phantom_path']) ||
        !is_executable(Application::$conf['phantom_path'])) {
      $errors[] = "Cannot find phantomJs executable";
    }
    if (empty(Application::$conf['generate_script']) ||
        !file_exists(Application::file(Application::$conf['generate_script']))) {
      $errors[] = "Cannot find phantomJs Generate script"; 
    }
    
    // Check/assign required parameters
    $defaults = array(
      'url' => NULL,
      'title' => NULL, 
      'site' => '',
      'author' => '',
      'author_data' => array('initial' => array(), 'current' => array(), 'others' => array()),
      'date' => date('Y-m-d'),
      'date_data' => array(time()),
      'post' => '',
    );
    $errors = array();
  
    foreach (array_keys($defaults) as $param) {
      if (isset($parameters[$param])) {
        if (is_array($defaults[$param])) {
          $this->{$param} = unserialize($parameters[$param]);
        } else {
          $this->{$param} = $parameters[$param];
        }
      } else if ($defaults[$param] !== NULL) {
        $this->{$param} = $defaults[$param];
      } else {
        $errors[] = "Missing required parameter: $param";
      }
    }
    
    if (!empty($errors)) {
      throw new Exception(implode(" ; ", $errors));
    }
 
    // Define generate mode
    if (reset($path) == 'generate') {
      $this->mode = 'generate';
    } else {
      $this->mode = 'preview';
    }
  }

  /**
   * processRequest
   *
   * Generate the PDF, and create a PageOutput
   */
  public function processRequest() {
    // Find a unique filename
    $type = '';
    if ($this->mode == 'generate') {
      $path_parts = parse_url($this->url);
      $basedir = $this->urlPart(preg_replace('%^.+?://%', '', $path_parts['host']));
      $basedir = $basedir . '/' . date('Y-m-d', end($this->date_data));
      if (!file_exists(Application::file($basedir))) {
        mkdir(Application::file($basedir), 0775, TRUE);
      }
      $base = $basedir . '/' . $this->urlPart($this->title);
   
      $gen_filename = $this->uniqueName($base, 'pdf'); 
      $type = 'pdf';
    } else {
      $gen_filename = $this->uniqueName(Application::$conf['preview_folder'] . '/' . substr(md5(rand()), 0, 5), 'png');
      $type = 'png';
    }
    $gen_url = Application::$conf['base_url'] . '/' . $gen_filename;
   
    // Prepare post data
    $post = $this->post;
    if ($post) {
      $post .= '&';
    }
    $post .= 'archive_url=' . urlencode($gen_url);
    // Invoke the script. PhantomJS sometimes times out for no reason
    // when there are many dependents - see https://github.com/ariya/phantomjs/issues/10652
    // So when we fail on timeout, we try twice.
    $attempts = 2;
    do {
      $status = Application::exec(Application::$conf['phantomjs_path'], array(
        0 => Application::$conf['generate_script'],
        '-url' => $this->url,
        '-dest' => Application::file($gen_filename),
        '-post' => $post
      ));
      $attempts--;
    } while ($status == Application::EXEC_TIMEOUT && $attempts > 0);
    if ($status > 0) {
      return new PageOutput(array(
        'status' => 0,
        'error' => 'There was an error generating the PDF document'
      ));
    }
    $generated = array(
      $type => $gen_url
    );
    if ($this->mode == 'generate') {
      // Attempts to set the meta data
      Application::exec(Application::$conf['exiftool'], array(
        0 => '-overwrite_original',
        '-Title=' => $this->title,
        '-Author=' => $this->author,
        1 => Application::file($gen_filename)
      ));
      // Create bibtex
      $bibtex_filename = $this->uniqueName($base, 'bib');
      $bibtex_url = Application::$conf['base_url'] . '/' . $bibtex_filename;
      file_put_contents($bibtex_filename, $this->bibTex($gen_url));
      $generated['bibtex'] = $bibtex_url;
      // Create Endnote
      $endnote_filename = $this->uniqueName($base, 'xml');
      $endnote_url = Application::$conf['base_url'] . '/' . $endnote_filename;
      file_put_contents($endnote_filename, $this->endnote($gen_url));
      $generated['endnote'] = $endnote_url;
      // Create RIS 
      $ris_filename = $this->uniqueName($base, 'ris');
      $ris_url = Application::$conf['base_url'] . '/' . $ris_filename;
      file_put_contents($ris_filename, $this->ris($gen_url));
      $generated['ris'] = $ris_url;
      // Save information about this citation in the database
      $citation = new citationModel($this->url, $gen_url, $this->title, $this->site, $this->author, $this->date, date('Y', end($this->date_data)), $generated);
      $citation->save();
    }
    $generated['status'] = 1;
    return new PageOutput($generated);
  }

  /**
   * Generate a bibtex entry
   */
  private function bibTex($archived_url) {
    $format = "@article {%key, title={%title}, year={%year}, url={%url},author={%author}}";
    return strtr($format, array(
      '%key' => $this->urlPart($this->url),
      '%title' => $this->title,
      '%year' => date('Y', end($this->date_data)),
      '%url' => $archived_url,
      '%author' => $this->author
    ));
  }

  /**
   * Generate an EndNote entry
   */
  private function endnote($archived_url) {
    // Build XML
    $xml = new SimpleXMLElement('<records></records>');
    $record = $xml->addChild('record');
    $record->addChild('ref-type', '13');
    if ($this->author_data['initial'] || $this->author_data['others']) {
      $contributors = $record->addChild('contributors');
      if ($this->author_data['initial']) {
        $authors = $contributors->addChild('authors');
        foreach ($this->author_data['initial'] as $author) {
          $this->endnoteXmlAuthor($authors, $author);
        }
      }
      if ($this->author_data['others']) {
        $secondary = $contributors->addChild('secondary-authors');
        foreach ($this->author_data['others'] as $author) {
          $this->endnoteXmlAuthor($secondary, $author);
        }
      }
    }
    $record->addChild('titles')->addChild('title', $this->title);
    $record->addChild('urls')->addChild('pdf-urls', $archived_url);
    return $xml->asXML();
  }

  /**
   * Generate an EndNote author entry
   */
  private function endnoteXMLAuthor($xml, $author) {
    $root = $xml->addChild('author');
    $map = array(
      'lastname' => 'last-name',
      'firstname' => 'fist-name',
      'title' => 'title',
      'institution' => 'corp-name'
    );
    foreach ($map as $dru => $end) {
      if (!empty($author[$dru])) {
        $root->addChild($end, $author[$dru]);
      }
    }
  }

  /**
   * Generate an RIS entry
   */
  private function ris($archive_url) {
    // As per http://en.wikipedia.org/wiki/RIS_%28file_format%29
    $ris = array();
    $ris[] = "TY  - ICOMM";
    $ris[] = "TI  - " . $this->title; 
    $ris[] = "PY  - " . date('Y', end($this->date_data));
    $ris[] = "DA  - " . date('Y-m-d', end($this->date_data));
    $ris[] = "UR  - " . $archive_url;
    $au_map = array(
      'AU' => $this->author_data['initial'],
      'A2' => $this->author_data['others']
    );
    foreach ($au_map as $type => $authors) {
      foreach ($authors as $author) {
        $line = $type."  - " . $author['lastname'];
        if (!empty($author['firstname'])) {
          $line .= ", " . $author['firstname'];
        }
        $ris[] = $line;
      }
    }
    return implode("\n", $ris);
  }

  /**
   * urlPart
   *
   * Transform a string into a dash separated string
   * that can be used as a part in a URL
   */
  private function urlPart($str) {
    $str = preg_replace('/[^-_a-zA-Z0-9\.]+/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    $str = preg_replace('/(^-+|-+$|\.+$)/', '', $str);
    return $str;
  }

  /**
   * uniqueName
   *
   * Given a base filename and an extention, return a unique filename
   */
   private function uniqueName($filename, $ext) {
     $count = 1;
     $unique = $filename;
     while (file_exists(Application::file($unique . '.' . $ext))) {
       $unique = $filename . '-' . $count;
       $count++;
     }
     return $unique . '.' . $ext;
   }
}

