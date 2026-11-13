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

/**
 * Implements hook_preprocess_html().
 *
 * Adds the site icons (files at the docroot: favicon.ico,
 * apple-touch-icon.png, favicons/). The classic favicon link comes from the
 * theme's "Shortcut icon" setting, which points at /favicon.ico.
 */
function telrhin_preprocess_html(&$variables) {
  $icons = array(
    array('rel' => 'icon', 'type' => 'image/png', 'sizes' => '32x32', 'href' => '/favicons/favicon-32x32.png'),
    array('rel' => 'icon', 'type' => 'image/png', 'sizes' => '192x192', 'href' => '/favicons/icon-192.png'),
    array('rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href' => '/apple-touch-icon.png'),
  );
  foreach ($icons as $icon) {
    drupal_add_html_head_link($icon);
  }
}
