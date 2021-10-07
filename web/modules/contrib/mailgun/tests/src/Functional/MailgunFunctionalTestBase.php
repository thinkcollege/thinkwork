<?php

namespace Drupal\Tests\mailgun\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\mailgun\MailgunHandlerInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Base test class for Mailgun functional tests.
 */
abstract class MailgunFunctionalTestBase extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['mailgun'];

  /**
   * Permissions required by the user to perform the tests.
   *
   * @var array
   */
  protected $permissions = [
    'administer mailgun',
  ];

  /**
   * An editable config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $mailgunConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->mailgunConfig = $this->config(MailgunHandlerInterface::CONFIG_NAME);
  }

}
