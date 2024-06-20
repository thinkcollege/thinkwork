<?php

namespace Drupal\Tests\gutenberg\Functional;

/**
 * Test that we can enable the module.
 *
 * @group gutenberg
 */
class EnableCloudTest extends ModuleEnableTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'gutenberg_cloud',
  ];

}
