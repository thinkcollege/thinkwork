<?php

namespace Drupal\gutenberg;

use Drupal\Core\Render\Element\RenderCallbackInterface;
use Drupal\filter\Entity\FilterFormat;

/**
 * Provides a trusted callback to alter the element type defaults.
 *
 * @see gutenberg_element_info_alter()
 */
class GutenbergElementInfoAlter implements RenderCallbackInterface {

  /**
   * #pre_render callback: Prerender function for text_formats with Gutenberg format enabled.
   */
  public static function preRender(array $element) {
    if (isset($element['#format']) && $element['#format'] == 'gutenberg') {
      $format = FilterFormat::load('gutenberg');
  
      // When html filtering is enabled, the value gets passed through the
      // xss_filter function twice, turning '&#13;' into '&amp;#13;' and breaking
      // the editor.
      if ($format->filters("filter_html")->getConfiguration()['status']) {
        $element['value']['#value'] = str_replace('&#13;', '', $element['value']['#value'], $count);
      }
    }
    return $element;
  }
}
