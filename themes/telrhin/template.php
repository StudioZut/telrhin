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

/**
 * Implements hook_preprocess_node().
 *
 * Blog posts (current "siteblog" and older "blog" types) show only the date,
 * as "Posted November 11, 2026", with no author name or picture. Spacing below the line
 * is in css/style.css.
 */
function telrhin_preprocess_node(&$variables) {
  if (in_array($variables['type'], array('siteblog', 'blog'), TRUE)) {
    $variables['submitted'] = t('Posted @date', array(
      '@date' => format_date($variables['created'], 'custom', 'F j, Y'),
    ));
    // The template prints the author picture in the same line; drop it too.
    $variables['user_picture'] = '';
  }
}
