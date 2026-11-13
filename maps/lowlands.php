<?php
/**
 * @file
 * Interactive map of the Lowlands (https://telrhin.com/maps/lowlands.php).
 *
 * Standalone page outside Drupal's page system, deployed to web/maps/ on the
 * live site. It bootstraps Drupal only to reuse the site's encyclopedia DB
 * connection (getzutdb() in telrhin_functions, credentials from settings.php)
 * and Drupal's output filters. Settlements (type 6) and geographic features
 * (type 8) with a MapGrid value get a marker; hovering shows the name, public
 * description and a link to the encyclopedia entry.
 */

define('DRUPAL_ROOT', dirname(__DIR__));
chdir(DRUPAL_ROOT);
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// The external DB connection is latin1 and some rows hold Windows-1252 bytes
// (curly quotes, nbsp). Drupal's filters return '' for invalid UTF-8, so
// convert those values; leave values that are already valid UTF-8 alone.
$to_utf8 = function ($value) {
  $value = (string) $value;
  return drupal_validate_utf8($value) ? $value : mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
};

// Map grid to pixel position. At 1600px wide each grid-paper square is
// 47.5px and each lettered/numbered grid quadrant is 4 squares = 190px.
// MapGrid is e.g. "C12" (column letter, row number); MapGrid_Sub is e.g. "b3"
// (square within the quadrant). Returns NULL for values that don't parse.
$grid_position = function ($grid, $sub) {
  if (!preg_match('/^([A-Z])([0-9]{1,2})$/', $grid, $g) || !preg_match('/^([a-z])([0-9])$/', $sub, $s)) {
    return NULL;
  }
  // Letters count from 0 (A = 0), as in the original layout math.
  $left = ((ord($g[1]) - ord('A')) * 190) + ((ord($s[1]) - ord('a') + 1) * 47.5);
  $top = (((int) $g[2] - 1) * 190) + ((int) $s[2] * 47.5);
  return array($left, $top);
};

$places = array();
$db = getzutdb();
if ($db) {
  // One query for both types; TypeID decides the marker class.
  $sql = "SELECT t.EntryID, t.Name, t.Description_Public, t.MapGrid, t.MapGrid_Sub, a.TypeID
    FROM Telrhin t JOIN Enc_Type_Assoc a ON t.EntryID = a.EntryID
    WHERE a.TypeID IN (6, 8) AND t.MapGrid <> ''
    ORDER BY t.Name ASC";
  $result = mysqli_query($db, $sql);
  if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
      $id = (int) $row['EntryID'];
      // An entry tagged with both types gets one marker (first type wins).
      if (isset($places[$id])) {
        continue;
      }
      $position = $grid_position($row['MapGrid'], $row['MapGrid_Sub']);
      if (!$position) {
        continue;
      }
      $places[$id] = array(
        'class' => ($row['TypeID'] == 8) ? 'geographic' : 'settlement',
        'name' => check_plain($to_utf8($row['Name'])),
        'description' => filter_xss_admin($to_utf8($row['Description_Public'])),
        'left' => $position[0],
        'top' => $position[1],
      );
    }
  }
  else {
    watchdog('telrhin_map', 'Lowlands map query failed: @error', array('@error' => mysqli_error($db)), WATCHDOG_ERROR);
  }
  mysqli_close($db);
}

// Geographic markers first, then settlements, each by name, as before
// (later ones stack on top).
uasort($places, function ($a, $b) {
  return strcmp($a['class'], $b['class']) ?: strcasecmp($a['name'], $b['name']);
});

drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
  <title>Map of the Lowlands</title>
  <meta charset="utf-8">
  <link rel="shortcut icon" href="https://www.telrhin.com/sites/default/files/telrhin_favicon_32.ico" type="image/vnd.microsoft.icon" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
  jQuery(document).ready(function($) {
    jQuery(".settlement, .geographic").hover(function() {
      jQuery(this).toggleClass('show-top');
      jQuery(this).children('div').eq(0).toggleClass('show');
    });
  })
</script>

<style>
#topnav {
  width: 1600px;
  height: 3em;
  margin: auto;
  font-family: helvetica, verdana, arial, sans-serif;
}
#themap {
  width: 1600px;
  margin: auto;
  position: relative;
}
#map-image {width: 1600px;}
.settlement {
  width: 20px;
  height: 20px;
  background-color: steelblue;
  position: absolute;
  border-radius:10px;
  font-family: helvetica, arial;
}
.geographic {
  width: 20px;
  height: 20px;
  background-color: mediumseagreen;
  position: absolute;
  border-radius:10px;
  font-family: helvetica, arial;
}
.place-title {background-color: #fff; white-space: nowrap; width:200px; margin-left: 22px; font-weight: 700;}
.hide {display: none;}
.show {display: block;}
.show-top {z-index: 500;}
.settlement-text {width: 200px; border-radius: 5px; margin-left: 22px; padding: 1em; font-family: helvetica, arial; background-color: #fff; color: #000;}

<?php
// Marker positions; the 14px centres the marker in its grid square.
foreach ($places as $id => $place) {
  echo '#s' . $id . ' {left: calc(' . $place['left'] . 'px + 14px); top: calc(' . $place['top'] . "px + 14px);}\n";
}
?>

/* a few manual tweaks for spacing */
/* pitus farm id 261 */
#s261 {left: calc(705px + 34px); top: calc(1071px + 34px);}
/* bagtran id 255 */
#s255 {left: calc(705px + 34px); top: calc(1091px + 34px);}
/* gardol id 598 */
#s598 {left: calc(658px + 34px); top: calc(502px + 34px);}
/* gredmon id 703 */
#s703 {left: calc(423px + 34px); top: calc(455px + 34px);}
/* Black Downs id 46 */
#s46 {left: calc(517px + 34px); top: calc(1014px + 34px);}
/* akani carnak id 317 */
#s317 {left: calc(1222px + 34px); top: calc(1719px + 34px);}
/* valley of drowned kings id 1013 */
#s1013 {left: calc(611px + 34px); top: calc(1202px + 34px);}
/* ahns barrot id 258 */
#s258 {left: calc(497px + 34px); top: calc(1091px + 34px);}
/* lindarin id 417 */
#s417 {left: calc(376px + 34px); top: calc(638px + 34px);}
/* waterbridge id 842 */
#s842 {left: calc(423px + 34px); top: calc(685px + 34px);}
/* terrin moors id 1044 */
#s1044 {left: calc(564px + 34px); top: calc(1061px + 34px);}
/* hammer flat id 1046 */
#s1046 {left: calc(423px + 34px); top: calc(497px + 34px);}

</style>

</head>
<body>

<div id='topnav'><a href='https://www.telrhin.com'>Back to Main Site</a></div>
<div id='themap'>
<img id='map-image' src='/downloads/lowlands_map.jpg' alt='Map of the Lowlands'>
<?php
if (!$places) {
  echo "<p>The map locations are temporarily unavailable.</p>\n";
}
foreach ($places as $id => $place) {
  echo "
      <div id='s" . $id . "' class='" . $place['class'] . "'>
      <span class='place-title'>" . $place['name'] . "</span>
        <div id='s" . $id . "-text' class='expand hide settlement-text'><em>" . $place['name'] . "</em><br>" . $place['description'] . "<br>[ <a href='https://www.telrhin.com/encyc/view?encycid=" . $id . "' target='_blank' rel='noopener'>encyclopaedia entry</a> ]</div>
      </div>";
}
?>
</div>

</body>
</html>
