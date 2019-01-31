<?php

namespace Drupal\glossify;

use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use DOMXPath;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render;

/**
 * Base implementation of tooltip filter type plugin.
 */
abstract class GlossifyBase extends FilterBase {

  /**
   * Convert terms in text to links.
   *
   * @param string $text
   *   The HTML text upon which the filter is acting.
   * @param array $terms
   *   The terms (array) to be replaced with links.
   *   structure: [$termname_lower => [
   *     ['id' => $id],
   *     ['name' => $term],
   *     ['name_norm' => $termname_lower],
   *     ['tip' => $tooltip],
   *   ]].
   * @param bool $case_sensitivity
   *   Case sensitive replace.
   * @param bool $first_only
   *   Replace only first match.
   * @param string $displaytype
   *   Type of tooltip/link.
   * @param string $urlpattern
   *   URL pattern to create links.
   *
   * @return string
   *   The original HTML with the term string replaced by links.
   */
  protected function parseTooltipMatch($text, array $terms, $case_sensitivity, $first_only, $displaytype, $urlpattern) {

    // Create dom document.
    $html_dom = Html::load($text);
    $xpath = new DOMXPath($html_dom);
    $pattern_parts = [];
    $matched = [];

    // Transform terms into normalized search pattern.
    foreach ($terms as $term) {
      $term_norm = preg_replace('/\s+/', ' ', preg_quote(trim($term->name_norm)));
      $term_norm = preg_replace('#/#', '\/', $term_norm);
      $pattern_parts[] = preg_replace('/ /', '\\s+', $term_norm);
    }
    $pattern = '/\b(' . implode('|', $pattern_parts) . ')\b/';
    if (!$case_sensitivity) {
      $pattern .= 'i';
    }

    // Process HTML.
    $text_nodes = $xpath->query('//text()[not(ancestor::a)]');
    foreach ($text_nodes as $original_node) {
      $text = $original_node->nodeValue;
      $hitcount = preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

      if ($hitcount > 0) {

        $offset = 0;
        $parent = $original_node->parentNode;
        $refnode = $original_node->nextSibling;

        $current_path = $this->currentPath();
        $parent->removeChild($original_node);
        foreach ($matches[0] as $i => $match) {
          $term_txt = $match[0];
          $term_pos = $match[1];
          $term_norm = preg_replace('/\s+/', ' ', $term_txt);
          $terms_key = $case_sensitivity ? $term_txt : strtolower($term_txt);

          // Insert any text before the term instance.
          $prefix = substr($text, $offset, $term_pos - $offset);
          $parent->insertBefore($html_dom->createTextNode($prefix), $refnode);

          $dom_fragment = $html_dom->createDocumentFragment();

          if ($current_path == str_replace('[id]', $terms[$terms_key]->id, $urlpattern)) {
            // Reinsert the found match if whe are on the page
            // this match points to.
            $dom_fragment->appendXML($term_txt);
          }
          elseif ($first_only && in_array($case_sensitivity ? $term_txt : $term_txt, $matched)) {
            // Reinsert the found match if only first match must be parsed.
            $dom_fragment->appendXML($term_txt);
          }
          else {
            $tip = '';
            if ($displaytype == 'links' || $displaytype == 'tooltips_links') {

              // Insert the matched term instance as link.
              if ($displaytype == 'tooltips_links') {
                $tip = $this->sanitizeTip($terms[$terms_key]->tip);
              }
              if (\Drupal::hasContainer()) {
                $tipurl = Url::fromUri('internal:' . str_replace('[id]', $terms[$terms_key]->id, $urlpattern));
              }
              else {
                $tipurl = str_replace('[id]', $terms[$terms_key]->id, $urlpattern);
              }
              $word_link = [
                '#theme' => 'glossify_link',
                '#word' => $term_txt,
                '#tip' => $tip,
                '#tipurl' => $tipurl,
              ];
              $word = $this->renderLink($word_link);
            }
            elseif ($displaytype == 'tooltips') {

              // Insert the matched term instance as tooltip.
              $tip = $this->sanitizeTip($terms[$terms_key]->tip);

              $word_tip = [
                '#theme' => 'glossify_tooltip',
                '#word' => $term_txt,
                '#tip' => $tip,
              ];
              $word = $this->renderTip($word_tip);
            }
            if (!$word) {
              // Dont let $word be empty, else appendXML method fails
              // with warning.
              $word = $term_txt;
            }
            $dom_fragment->appendXML($word);
            $matched[] = ($case_sensitivity ? $term_txt : $term_txt);
          }
          $parent->insertBefore($dom_fragment, $refnode);

          $offset = $term_pos + strlen($term_txt);

          // Last match, append remaining text.
          if ($i == $hitcount - 1) {
            $suffix = substr($text, $offset);
            $parent->insertBefore($html_dom->createTextNode($suffix), $refnode);
          }
        }
      }
    }
    $text = Html::serialize($html_dom);

    return $text;
  }

  /**
   * Render tip for found match.
   */
  protected function renderTip($word_tip) {
    return render($word_tip);
  }

  /**
   * Render link for found match.
   */
  protected function renderLink($word_link) {
    return render($word_link);
  }

  /**
   * Get current path.
   */
  protected function currentPath() {
    return \Drupal::service('path.current')->getPath();
  }

  /**
   * Cleanup and truncate tip text.
   */
  private function sanitizeTip($tip) {

    // Get rid of HTML.
    $tip = strip_tags($tip);

    // Maximise tooltip text length.
    $tip = Unicode::truncate($tip, 300, TRUE, TRUE);

    return $tip;
  }

}
