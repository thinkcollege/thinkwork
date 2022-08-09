<?php

namespace Drupal\Tests\glossify\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\glossify\GlossifyBase;
use Drupal\Component\Utility\Unicode;

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
  public function testParseTooltipMatch($text, $terms, $case_sensitivity, $first_only, $displaytype, $tooltip_truncate, $urlpattern, $output) {
    // Instantiate dummy object.
    $dummyTooltip = new DummyTooltip(
      $terms,
      $case_sensitivity,
      $first_only,
      $displaytype,
      $tooltip_truncate,
      $urlpattern
    );
    $replacement = $dummyTooltip->process($text, 'nl');
    $this->assertEquals($output, $replacement);
  }

  /**
   * Test data provider.
   */
  public function parseTooltipMatchData() {
    $term = new \stdClass();
    $term->id = '1';
    $term->name = 'RT';
    $term->name_norm = 'RT';
    $term->tip = "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.";

    $term2 = new \stdClass();
    $term2->id = '2';
    $term2->name = 'group';
    $term2->name_norm = 'group';
    $term2->tip = "Lorem Ipsum";

    $term3 = new \stdClass();
    $term3->id = '3';
    $term3->name = 'ווערטער';
    $term3->name_norm = 'ווערטער';
    $term3->tip = "Lorem Ipsum";

    $term4 = new \stdClass();
    $term4->id = '4';
    $term4->name = 'ätsch';
    $term4->name_norm = 'ätsch';
    $term4->tip = "Lorem Ipsum";

    // UTF-8 2-byte Characters.
    $term5 = new \stdClass();
    $term5->id = '5';
    $term5->name = 'Æŧśĉĥ';
    $term5->name_norm = 'Æŧśĉĥ';
    $term5->tip = "Lorem Ipsum";

    // UTF-8 3-byte Characters.
    $term6 = new \stdClass();
    $term6->id = '6';
    $term6->name = 'ଔଡଵଶଷ';
    $term6->name_norm = 'ଔଡଵଶଷ';
    $term6->tip = "Lorem Ipsum";

    // UTF-8 4-byte Characters.
    $term7 = new \stdClass();
    $term7->id = '7';
    $term7->name = '𒀆𒀇𒀈𒀈𒀊𒀋';
    $term7->name_norm = '𒀆𒀇𒀈𒀈𒀊𒀋';
    $term7->tip = "Lorem Ipsum";

    $data = [
      'set1' => [
        'text' => 'Simple plain text with RT as replacement term',
        'terms' => [$term->name_norm => $term],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
        'tooltip_truncate' => FALSE,
        'urlpattern' => '',
        'output' => 'Simple plain text with <span title="' . $term->tip . '">RT</span> as replacement term',
      ],
      'set2' => [
        'text' => '<p>Simple HTML with <b>RT</b> and rt as replacement term</p>',
        'terms' => [$term->name_norm => $term],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips_links',
        'tooltip_truncate' => FALSE,
        'urlpattern' => '/random/testpattern',
        'output' => '<p>Simple HTML with <b><a href="/random/testpattern" title="' . $term->tip . '">RT</a></b> and rt as replacement term</p>',
      ],
      'set3' => [
        'text' => 'Simple plain text with RT as replacement term',
        'terms' => [$term->name_norm => $term],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
        'tooltip_truncate' => FALSE,
        'urlpattern' => '',
        'output' => 'Simple plain text with <span title="' . $term->tip . '">RT</span> as replacement term',
      ],
      'set4' => [
        'text' => 'Simple plain text with RT as replacement term',
        'terms' => [$term->name_norm => $term],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
        'tooltip_truncate' => TRUE,
        'urlpattern' => '',
        'output' => 'Simple plain text with <span title="' . Unicode::truncate($term->tip, 300, TRUE, TRUE) . '">RT</span> as replacement term',
      ],
      'set5' => [
        'text' => 'Simple plain text with the multi-byte word Gebäck and RT as replacement term',
        'terms' => [$term->name_norm => $term],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
        'tooltip_truncate' => FALSE,
        'urlpattern' => '',
        'output' => 'Simple plain text with the multi-byte word Gebäck and <span title="' . $term->tip . '">RT</span> as replacement term',
      ],
      'set6' => [
        'text' => 'Simple plain text with the multi-byte word சொல் and RT as replacement term',
        'terms' => [$term->name_norm => $term],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
        'tooltip_truncate' => FALSE,
        'urlpattern' => '',
        'output' => 'Simple plain text with the multi-byte word சொல் and <span title="' . $term->tip . '">RT</span> as replacement term',
      ],
      'set7' => [
        'text' => 'According to Article 312 of Commission Delegated Regulation (EU) 2015/35, the ORSA supervisory report is to be submitted on an annual basis and within 2 weeks after concluding the assessment. For the purpose of preparing theÂ ORSA supervisory report for Allianz Group, Group Risk defines annually deadlines for deliverables by OEs and communicates them to the CROs of the respective OEs.',
        'terms' => [$term2->name_norm => $term2],
        'case_sensitivity' => FALSE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
        'tooltip_truncate' => FALSE,
        'urlpattern' => '',
        'output' => 'According to Article 312 of Commission Delegated Regulation (EU) 2015/35, the ORSA supervisory report is to be submitted on an annual basis and within 2 weeks after concluding the assessment. For the purpose of preparing theÂ ORSA supervisory report for Allianz <span title="' . $term2->tip . '">Group</span>, <span title="' . $term2->tip . '">Group</span> Risk defines annually deadlines for deliverables by OEs and communicates them to the CROs of the respective OEs.',
      ],
      'set8' => [
        'text' => 'Simple plain text with some multi-byte words orð, சொற்கள் and ווערטער as replacement term',
        'terms' => [$term3->name_norm => $term3],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
       'tooltip_truncate' => FALSE,
        'urlpattern' => '',
        'output' => 'Simple plain text with some multi-byte words orð, சொற்கள் and <span title="' . $term3->tip . '">' . $term3->name . '</span> as replacement term',
      ],
      'set9' => [
        'text' => 'Simple plain text with Ätsch as replacement term',
        'terms' => [$term4->name_norm => $term4],
        'case_sensitivity' => FALSE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
        'tooltip_truncate' => FALSE,
        'urlpattern' => '',
        'output' => 'Simple plain text with <span title="' . $term4->tip . '">Ätsch</span> as replacement term',
      ],
      // UTF-8 2-byte Characters in haystack.
      'set10' => [
        'text' => 'Simple plain Æŧśĉĥ text Æŧśĉĥ with RT as replacement term',
        'terms' => [$term->name_norm => $term],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
        'tooltip_truncate' => FALSE,
        'urlpattern' => '',
        'output' => 'Simple plain Æŧśĉĥ text Æŧśĉĥ with <span title="' . $term->tip . '">RT</span> as replacement term',
      ],
      // UTF-8 2-byte Characters in haystack and subject.
      'set11' => [
        'text' => 'Simple plain Æŧśĉĥ text Æŧśĉĥ with Æŧśĉĥ as replacement term',
        'terms' => [$term5->name_norm => $term5],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
        'tooltip_truncate' => FALSE,
        'urlpattern' => '',
        'output' => 'Simple plain <span title="' . $term5->tip . '">Æŧśĉĥ</span> text <span title="' . $term5->tip . '">Æŧśĉĥ</span> with <span title="' . $term5->tip . '">Æŧśĉĥ</span> as replacement term',
      ],
      // UTF-8 3-byte Characters in haystack.
      'set12' => [
        'text' => 'Simple plain ଔଡଵଶଷ text ଔଡଵଶଷ with RT as replacement term',
        'terms' => [$term->name_norm => $term],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
        'tooltip_truncate' => FALSE,
        'urlpattern' => '',
        'output' => 'Simple plain ଔଡଵଶଷ text ଔଡଵଶଷ with <span title="' . $term->tip . '">RT</span> as replacement term',
      ],
      // UTF-8 3-byte Characters in haystack and subject.
      'set13' => [
        'text' => 'Simple plain ଔଡଵଶଷ text ଔଡଵଶଷ with ଔଡଵଶଷ as replacement term',
        'terms' => [$term6->name_norm => $term6],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
        'tooltip_truncate' => FALSE,
        'urlpattern' => '',
        'output' => 'Simple plain <span title="' . $term6->tip . '">ଔଡଵଶଷ</span> text <span title="' . $term6->tip . '">ଔଡଵଶଷ</span> with <span title="' . $term6->tip . '">ଔଡଵଶଷ</span> as replacement term',
      ],
      // UTF-8 4-byte Characters in haystack.
      'set14' => [
        'text' => 'Simple plain 𒀆𒀇𒀈𒀈𒀊𒀋 text 𒀆𒀇𒀈𒀈𒀊𒀋 with RT as replacement term',
        'terms' => [$term->name_norm => $term],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
        'tooltip_truncate' => FALSE,
        'urlpattern' => '',
        'output' => 'Simple plain 𒀆𒀇𒀈𒀈𒀊𒀋 text 𒀆𒀇𒀈𒀈𒀊𒀋 with <span title="' . $term->tip . '">RT</span> as replacement term',
      ],
      // UTF-8 4-byte Characters in haystack and subject.
      'set15' => [
        'text' => 'Simple plain 𒀆𒀇𒀈𒀈𒀊𒀋 text 𒀆𒀇𒀈𒀈𒀊𒀋 with 𒀆𒀇𒀈𒀈𒀊𒀋 as replacement term',
        'terms' => [$term7->name_norm => $term7],
        'case_sensitivity' => TRUE,
        'first_only' => FALSE,
        'displaytype' => 'tooltips',
        'tooltip_truncate' => FALSE,
        'urlpattern' => '',
        'output' => 'Simple plain <span title="' . $term7->tip . '">𒀆𒀇𒀈𒀈𒀊𒀋</span> text <span title="' . $term7->tip . '">𒀆𒀇𒀈𒀈𒀊𒀋</span> with <span title="' . $term7->tip . '">𒀆𒀇𒀈𒀈𒀊𒀋</span> as replacement term',
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

  /**
   * Taxonomy terms.
   *
   * @var array
   */
  private $terms;

  /**
   * Cas sensitivity.
   *
   * @var bool
   */
  private $caseSensitivity;

  /**
   * First only.
   *
   * @var bool
   */
  private $firstOnly;

  /**
   * Displaytype.
   *
   * @var string
   */
  private $displaytype;

  /**
   * Truncate tooltip.
   *
   * @var bool
   */
  private $tooltipTruncate;

  /**
   * Urlpattern.
   *
   * @var string
   */
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
   * @param bool $tooltip_truncate
   *   Whether to truncate tooltip.
   * @param string $urlpattern
   *   URL pattern to create links.
   */
  public function __construct(array $terms, $case_sensitivity, $first_only, $displaytype, $tooltip_truncate, $urlpattern) {
    $this->terms = $terms;
    $this->caseSensitivity = $case_sensitivity;
    $this->firstOnly = $first_only;
    $this->displaytype = $displaytype;
    $this->tooltipTruncate = $tooltip_truncate;
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
    return $this->parseTooltipMatch(
      $text,
      $this->terms,
      $this->caseSensitivity,
      $this->firstOnly,
      $this->displaytype,
      $this->tooltipTruncate,
      $this->urlpattern,
      $langcode
    );
  }

}
