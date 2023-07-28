<?php

namespace Drupal\Tests\mailgun\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\mailgun\MailgunHandlerInterface;

/**
 * Mailgun kernel test base class.
 */
abstract class MailgunKernelTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['mailgun'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['mailgun']);
  }

  /**
   * Sets the Mailgun configuration value.
   *
   * @param string $config_name
   *   The config key name.
   * @param string $config_value
   *   The config key value.
   */
  protected function setConfigValue($config_name, $config_value) {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $config_factory->getEditable(MailgunHandlerInterface::CONFIG_NAME)
      ->set($config_name, $config_value)
      ->save();
  }

}
