<?php

namespace Drupal\Tests\tagify\FunctionalJavascript\FieldWidget;

use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\Tests\tagify\FunctionalJavascript\TagifyJavascriptTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests tagify entity reference widget.
 *
 * @group tagify
 */
class TagifyEntityReferenceAutocompleteWidgetTest extends TagifyJavascriptTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'tagify',
    // Prevent tests from failing due to 'RuntimeException' with AJAX request.
    'js_testing_ajax_request_test',
  ];

  /**
   * Test a single value widget.
   *
   * @dataProvider providerTestSingleValueWidget
   */
  public function testSingleValueWidget($match_operator, $autocreate) {
    // Create a new entity reference field with tagify widget.
    $this->createField('tagify', 'node', 'test', 'entity_reference', [
      'target_type' => 'entity_test_mulrevpub',
    ], [
      'handler' => 'default:entity_test_mulrevpub',
      'handler_settings' => [
        'auto_create' => $autocreate,
      ],
    ], 'tagify_entity_reference_autocomplete_widget', [
      'match_operator' => $match_operator,
      'match_limit' => 10,
      'suggestions_dropdown' => 1,
      'show_entity_id' => 0,
    ]);

    // Add references to the new field.
    EntityTestMulRevPub::create(['name' => 'foo'])->save();
    EntityTestMulRevPub::create(['name' => 'bar'])->save();
    EntityTestMulRevPub::create(['name' => 'bar foo'])->save();
    EntityTestMulRevPub::create(['name' => 'foo bar'])->save();
    EntityTestMulRevPub::create(['name' => 'tag'])->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/node/add/test');
    $page->fillField('title[0][value]', 'Test node');
    $this->click('.tagify__input');

    // Write value to get suggestion.
    $page->find('css', '.tagify__input')->setValue('foo');
    $assert_session->waitForElement('css', '.tagify__dropdown__item');
    $assert_session->waitForElementVisible('css', '.tagify__dropdown__item--active');

    // Output the new HTML.
    $this->htmlOutput($page->getHtml());

    $page->find('css', '.tagify__dropdown__item--active')->doubleClick();

    $page->pressButton('Save');

    $node = $this->getNodeByTitle('Test node', TRUE);

    // Check if the node is an object.
    if (is_object($node)) {
      $this->assertEquals([['target_id' => 1]], $node->tagify->getValue());
    }
  }

  /**
   * Data provider for testSingleValueWidget().
   *
   * @return array
   *   The data.
   */
  public function providerTestSingleValueWidget() {
    return [
      ['CONTAINS', TRUE],
      ['STARTS_WITH', TRUE],
    ];
  }

}
