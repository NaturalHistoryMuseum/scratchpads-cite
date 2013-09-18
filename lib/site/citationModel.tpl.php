<?php
/**
 * Available variables:
 *
 * - url
 * - genUrl
 * - title
 * - author
 * - date
 * - resources
 */
?>
<?php
  $url = htmlspecialchars($url);
  $url = "<a href='$url'>$url</a>";
  $genUrl = htmlspecialchars($genUrl);
  $genUrl = "<a href='$genUrl'>$genUrl</a>";
  $title = htmlspecialchars($title);
  $site = htmlspecialchars($site);
  $dateOfSnapshot = htmlspecialchars($dateOfSnapshot);
  $date = htmlspecialchars($date);
  $year = htmlspecialchars($year);
  $author = htmlspecialchars($author);
  $resource_map = array(
    'bibtex' => 'BibTex',
    'endnote' => 'EndNote XML',
    'ris' => 'RIS'
  );
  $info_table = array(
    'Page title' => $title,
    'Site' => $site,
    'Original URL' => $url,
    'Date of snapshot' => $dateOfSnapshot,
    'Last modification date (if known)' => $date,
    'Author(s)' => $author
  );
  $info_table_r = '<table>';
  foreach($info_table as $k => $v) {
    $info_table_r .= '<tr><th>' . $k . '</th><td>' . $v . '</td></tr>';
  }
  $info_table_r .= '</table>';
?>
<div class="archive-entry">
  <h2>Archive of <em><?php echo $title; ?></em> from <?php echo $site; ?> on <?php echo $dateOfSnapshot; ?></h2>
  <h3>Information</h3>
  <div class="information">
    <table>
      <thead>
        <th>Archive URL</th>
        <th>Info</th>
      </thead>
      <tbody>
      <tr>
        <td><?php echo $genUrl; ?></td>
        <td><?php echo $info_table_r; ?></td>
      </tr>
      </tbody>
    </table>
  </div>
  <h3>Citation files</h3>
  <div class="citation-files">
    <table>
    <?php
      foreach ($resources as $k => $v) {
        if ($k == 'pdf') {
          continue;
        }
        if (isset($resource_map[$k])) {
          $k = $resource_map[$k];
        } else {
          $k = ucwords(htmlspecialchars($k));
        }
        $v = htmlspecialchars($v);
        $v = "<a href='$v'>$v</a>";
        echo '<tr><th>' . $k . '</th><td>' . $v . '</td></tr>';
      }
    ?>
    </table>
  </div>
  <h3>Please cite this page as</h3>
  <div class="citethispage-as">
    <?php echo "$author ($year) $title. <em>$site</em>. $url"; ?>
  </div>
</div>
