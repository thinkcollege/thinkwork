<?php

namespace Drupal\Tests\views_accordion\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the JavaScript functionality of the Views Accordion module.
 *
 * @group views_accordion
 */
class ViewsAccordionTest extends JavascriptTestBase {
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views_accordion_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create a set of nodes for testing.
    $this->createNode();
    $this->createNode();
  }

  /**
   * Tests Views Accordion functionality.
   */
  public function testViewsAccordion() {
    $this->drupalGet('views-accordion-test');
    // Assert our JS settings are available.
    $settings = $this->getDrupalSettings();
    $this->assertArrayHasKey('views_accordion', $settings, 'Views accordion JS settings avaialable');

    // Assert that the first row is visible but not the second.
    $this->getSession()->getDriver()->isVisible($this->cssSelectToXpath('#ui-id-2'), 'Row one is shown');
    $this->assertFalse($this->getSession()->getDriver()->isVisible($this->cssSelectToXpath('#ui-id-4')), 'Row two is collapsed');

    // Assert that clicking the first row does not close it.
    $this->click('#ui-id-1');
    $this->getSession()->getDriver()->isVisible($this->cssSelectToXpath('#ui-id-2'), 'Row one is stil shown');

    // Assert the header icons are displayed in the correct place.
    $this->getSession()->getDriver()->isVisible($this->cssSelectToXpath('#ui-id-1 span.ui-icon-triangle-1-s'), 'Active header icon is visible');
    $this->getSession()->getDriver()->isVisible($this->cssSelectToXpath('#ui-id-3 span.ui-icon-triangle-1-e'), 'Inactive header icon is visible');
  }

}
