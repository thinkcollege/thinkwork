<?php

namespace Drupal\Tests\gutenberg\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test that we can enable the module.
 *
 * @group gutenberg
 */
class ModuleEnableTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'gutenberg',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A simple test that enables the module.
   */
  public function testEnable() {
    // Assert something so the test runs.
    self::assertTrue(TRUE);
  }

}
