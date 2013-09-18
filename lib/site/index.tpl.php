<?php
/**
 * Available variables:
 *
 * $base_url
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN"
  "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" version="XHTML+RDFa 1.0" dir="ltr"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:dc="http://purl.org/dc/terms/"
  xmlns:foaf="http://xmlns.com/foaf/0.1/"
  xmlns:og="http://ogp.me/ns#"
  xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
  xmlns:sioc="http://rdfs.org/sioc/ns#"
  xmlns:sioct="http://rdfs.org/sioc/types#"
  xmlns:skos="http://www.w3.org/2004/02/skos/core#"
  xmlns:xsd="http://www.w3.org/2001/XMLSchema#">
  <head profile="http://www.w3.org/1999/xhtml/vocab">
    <link type="text/css" rel="stylesheet" href="<?php echo $base_url; ?>/lib/site/style.css" media="all" />
  <head>
  <body>
    <div id="page">
      <div id="header">
        <a href="<?php echo $base_url; ?>"><img width='318px' height='68px' src="http://scratchpads.eu/sites/all/themes/scratchpads_eu/images/logo-green.png" /></a>
      </div>
      <div id="content">
        <h1>Scratchpads page archive</h1>
        <?php foreach ($citations as $citation) {
                echo $citation->render();
              }
        ?>
      </div>
      <div id="footer">
      </div>
    </div>
  </body>
</html>
