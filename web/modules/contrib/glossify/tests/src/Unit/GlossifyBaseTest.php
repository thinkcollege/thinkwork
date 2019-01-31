<?php

namespace Drupal\Tests\glossify\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\glossify\GlossifyBase;

/**
 * @coversDefaultClass \Drupal\glossify\GlossifyBase
 *
 * @group glossify
 */
class GlossifyBaseTest extends UnitTestCase {

  /**
   * @covers ::parseTooltipMatch
   * @dataProvider parseTooltipMatchData
   */
  public function testParseTooltipMatch($text, $terms, $case_sensitivity, $first_only, $displaytype, $urlpattern, $output) {

    // Instantiate dummy object.
    $dummyTooltip = new DummyTooltip(
      $terms,
      $case_sensitivity,
      $first_only,
      $displaytype,
      $urlpattern
    );
    $replacement = $dummyTooltip->process($text, 'nl');
    $this->assertEquals($replacement, $output);
  }

  /**
   * {@inheritdoc}
   */
  public function parseTooltipMatchData() {
    $term = new \stdClass();
    $term->id = '1';
    $term->name = 'RT';
    $term->name_norm = 'RT';
    $term->tip = 'means replacement term';
    $data = [
      'set1' => [
        'text' => 'Simple plain text with RT as replacement term',
        'terms' => [$term->name_norm => $term],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
        'urlpattern' => '',
        'output' => 'Simple plain text with <span title="' . $term->tip . '">RT</span> as replacement term',
      ],
      'set2' => [
        'text' => '<p>Simple HTML with <b>RT</b> and rt as replacement term</p>',
        'terms' => [$term->name_norm => $term],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips_links',
        'urlpattern' => '/random/testpattern',
        'output' => '<p>Simple HTML with <b><a href="/random/testpattern" title="' . $term->tip . '">RT</a></b> and rt as replacement term</p>',
      ],
    ];
    return $data;
  }

}

/**
 * Dummy tooltip object.
 *
 * Makes testing GlossifyBase possible as its base class.
 */
class DummyTooltip extends GlossifyBase {

  private $terms;
  private $caseSensitivity;
  private $firstOnly;
  private $displaytype;
  private $urlpattern;

  /**
   * Constructor.
   *
   * @param array $terms
   *   List of words with metadata.
   * @param bool $case_sensitivity
   *   Case sensitive replace.
   * @param bool $first_only
   *   Replace only first match.
   * @param string $displaytype
   *   Type of tooltip/link.
   * @param string $urlpattern
   *   URL pattern to create links.
   */
  public function __construct(array $terms, $case_sensitivity, $first_only, $displaytype, $urlpattern) {
    $this->terms = $terms;
    $this->caseSensitivity = $case_sensitivity;
    $this->firstOnly = $first_only;
    $this->displaytype = $displaytype;
    $this->urlpattern = $urlpattern;
  }

  /**
   * {@inheritdoc}
   */
  protected function renderTip($word_tip) {
    return '<span title="' . $word_tip['#tip'] . '">' . $word_tip['#word'] . '</span>';
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink($word_link) {
    return '<a href="' . $word_link['#tipurl'] . '"  title="' . $word_link['#tip'] . '">' . $word_link['#word'] . '</a>';
  }

  /**
   * {@inheritdoc}
   */
  protected function currentPath() {
    return '/some/internal/path';
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $replacement = $this->parseTooltipMatch(
      $text,
      $this->terms,
      $this->caseSensitivity,
      $this->firstOnly,
      $this->displaytype,
      $this->urlpattern
    );
    return $replacement;
  }

}
