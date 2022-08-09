<?php

namespace Drupal\glossify;

use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Component\Utility\Unicode;

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
   * @param bool $tooltip_truncate
   *   Whether to truncate tooltip.
   * @param string $urlpattern
   *   URL pattern to create links.
   * @param string $langcode
   *   Langcode identifier.
   *
   * @return string
   *   The original HTML with the term string replaced by links.
   */
  protected function parseTooltipMatch($text, array $terms, $case_sensitivity, $first_only, $displaytype, $tooltip_truncate, $urlpattern, $langcode) {
    if (!Unicode::validateUtf8($text)) {
      \Drupal::logger('glossify')
        ->debug($this->t('The text is apparently is not a valid utf8 charset: @text', [
          '@text' => $text,
        ]));
      return $text;
    }

    // Create dom document.
    $html_dom = Html::load($text);
    $xpath = new \DOMXPath($html_dom);
    $pattern_parts = $replaced = [];

    // Transform terms into normalized search pattern.
    $options = 'u';
    if (!$case_sensitivity) {
      $options .= 'i';
    }
    foreach ($terms as $term) {
      $pattern_parts[$term->name] = '/\b(' . preg_quote($term->name, '/') . ')\b/' . $options;
    }
    ksort($pattern_parts);

    // Process HTML.
    $text_nodes = $xpath->query('//text()[not(ancestor::a) and not(ancestor::span[@class="glossify-exclude"])]');
    foreach ($text_nodes as $original_node) {
      $text = $original_node->nodeValue;
      if (empty(trim($text))) {
        continue;
      }
      $matches = [];
      foreach ($pattern_parts as $pattern => $pattern_part) {
        preg_match_all($pattern_part, $text, $matches_part, PREG_OFFSET_CAPTURE);
        if (count($matches_part[0])) {
          foreach ($matches_part[0] as $match_part) {
            $term = $match_part[0];
            $byte_offset = $match_part[1];
            $byte_length = strlen($match_part[0]);
            $char_offset = mb_strlen(substr($text, 0, $byte_offset));
            $char_length = mb_strlen($match_part[0]);
            $verification = mb_substr($text, $char_offset, $char_length);

            $matches[$char_offset] = [
              'term' => $term,
              'pattern' => $pattern,
              'byte_offset' => $byte_offset,
              'char_offset' => $char_offset,
              'byte_length' => $byte_length,
              'char_length' => $char_length,
              'verification' => $verification,
            ];
          }
        }
      }
      // Sort by position in text.
      ksort($matches);

      // Remove terms inside other terms (Example: 'Ipsum' in 'Lorem Ipsum').
      foreach ($matches as $key => $match) {
        $matchesToUnset = array_filter($matches, function ($k) use ($key, $match) {
          $end_of_match = $match['char_offset'] + $match['char_length'];
          // Check if the match isn't itself and is located inside the current
          // match.
          if ($key !== $k && ($k < $end_of_match && $k >= $key)) {
            return $k;
          }
        }, ARRAY_FILTER_USE_KEY);

        foreach ($matchesToUnset as $keyToUnset => $unset) {
          unset($matches[$keyToUnset]);
        }
      }

      if (count($matches) > 0) {
        $offset = $loop_count = 0;
        $parent = $original_node->parentNode;
        $refnode = $original_node->nextSibling;

        $current_path = $this->currentPath();
        $parent->removeChild($original_node);
        foreach ($matches as $char_offset => $match) {
          $loop_count += 1;
          $term_txt = $match['term'];
          $terms_key = $case_sensitivity ? $term_txt : mb_strtolower($term_txt);

          // Works around an issue where terms can't be found when comparing a
          // match to the original term name.
          $term = $terms[$terms_key];

          // Insert any text before the term instance.
          $prefix = mb_substr($text, $offset, $match['char_offset'] - $offset);
          $parent->insertBefore($html_dom->createTextNode($prefix), $refnode);

          $dom_fragment = $html_dom->createDocumentFragment();

          if ($current_path == str_replace('[id]', $term->id, $urlpattern)) {
            // Reinsert the found match if whe are on the page
            // this match points to.
            $dom_fragment->appendXML($term_txt);
          }
          elseif ($first_only && in_array(strtolower($term_txt), array_map('strtolower', ($replaced)))) {
            // Reinsert the found match if only first match must be parsed.
            $dom_fragment->appendXML($term_txt);
          }
          else {
            $tip = '';
            if ($displaytype == 'links' || $displaytype == 'tooltips_links') {

              // Insert the matched term instance as link.
              if ($displaytype == 'tooltips_links') {
                $tip = $this->sanitizeTip($term->tip, $tooltip_truncate);
              }
              if (\Drupal::hasContainer()) {
                $tipurl = Url::fromUri('internal:' . str_replace('[id]', $term->id, $urlpattern));
              }
              else {
                $tipurl = str_replace('[id]', $term->id, $urlpattern);
              }
              $word_link = [
                '#theme' => 'glossify_link',
                '#word' => $term_txt,
                '#tip' => $tip,
                '#tipurl' => $tipurl,
              ];
              $word = $this->renderLink($word_link);
            }
            else {
              // Has to be 'tooltips'.
              // Insert the matched term instance as tooltip.
              $tip = $this->sanitizeTip($term->tip, $tooltip_truncate);

              $word_tip = [
                '#theme' => 'glossify_tooltip',
                '#word' => $term_txt,
                '#tip' => $tip,
                '#langcode' => $langcode,
              ];
              $word = $this->renderTip($word_tip);
            }
            $dom_fragment->appendXML($word);
            $replaced[] = $term_txt;
          }
          $parent->insertBefore($dom_fragment, $refnode);

          $offset = $match['char_offset'] + $match['char_length'];

          // Last match, append remaining text.
          if ($loop_count == count($matches)) {
            $suffix = mb_substr($text, $offset);
            $parent->insertBefore($html_dom->createTextNode($suffix), $refnode);
          }
        }
      }
    }
    return Html::serialize($html_dom);
  }

  /**
   * Render tip for found match.
   */
  protected function renderTip($word_tip) {
    return trim(render($word_tip));
  }

  /**
   * Render link for found match.
   */
  protected function renderLink($word_link) {
    return trim(render($word_link));
  }

  /**
   * Get current path.
   */
  protected function currentPath() {
    return \Drupal::service('path.current')->getPath();
  }

  /**
   * Cleanup and truncate tip text.
   *
   * @param string $tip
   *   The tooltip string.
   * @param bool $truncate
   *   Whether to truncate the tooltip string.
   *
   * @return string
   *   The prepared tooltip string.
   */
  private function sanitizeTip($tip, $truncate = TRUE) {
    // Get rid of HTML.
    $tip = strip_tags($tip);

    // Maximise tooltip text length.
    if ($truncate) {
      $tip = Unicode::truncate($tip, 300, TRUE, TRUE);
    }
    return $tip;
  }

}
