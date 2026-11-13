<?php
/**
 * @file
 * The primary PHP file for this theme.
 */

/**
 * Implements hook_preprocess_field().
 *
 * Styles blog post tags (field_tags) as Bootstrap buttons, matching the
 * hand-made "Sort by" buttons on the blog page. Spacing is in css/style.css.
 */
function telrhin_preprocess_field(&$variables) {
  if ($variables['element']['#field_name'] == 'field_tags') {
    foreach ($variables['items'] as $delta => $item) {
      // taxonomy_term_reference_link items are '#type' => 'link' elements.
      $variables['items'][$delta]['#options']['attributes']['class'][] = 'btn';
      $variables['items'][$delta]['#options']['attributes']['class'][] = 'btn-primary';
    }
  }
}
