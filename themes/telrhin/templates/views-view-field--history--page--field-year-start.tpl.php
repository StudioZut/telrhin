<?php

/**
 * @file
 * This template is used to print a single field in a view.
 *
 * It is not actually used in default Views, as this is registered as a theme
 * function which has better performance. For single overrides, the template is
 * perfectly okay.
 *
 * Variables available:
 * - $view: The view object
 * - $field: The field handler object that can process the input
 * - $row: The raw SQL result that can be used
 * - $output: The processed output that will normally be used.
 *
 * When fetching output from the $row, this construct should be used:
 * $data = $row->{$field->field_alias}
 *
 * The above will guarantee that you'll always get the correct data,
 * regardless of any changes in the aliasing that might happen if
 * the view is modified.
 */
?>
<?php 

  // -1601 is the actual year, but to make it "year 1" and not "year 0" adding 1602
  $fooVar = ($output + 1602);

  print "<strong>" . $output . "</strong> <span style='font-size: small;'>(year " . $fooVar . " Andahar)</span>"; 

  //var_dump($row);

?>
