<?php

class citationModel extends Model {
  public $url;
  public $genUrl;
  public $title;
  public $site;
  public $author;
  public $date;
  public $year;
  public $resources;

  /**
   * Create a new citation
   */
  public function __construct($url, $genUrl, $title, $site, $author, $date, $year, $resources) {
    $this->url = $url;
    $this->genUrl = $genUrl;
    $this->title = $title;
    $this->site = $site;
    $this->author = $author;
    $this->date = $date;
    $this->year = $year;
    $this->dateOfSnapshot = date('Y-m-d', time());
    $this->resources = $resources;
  } 
}

Model::register('citationModel', 'genUrl', array('title', 'site', 'author', 'year'));
