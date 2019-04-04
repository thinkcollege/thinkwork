<?php

namespace Drupal\mailgun;

use Drupal\Core\Config\ConfigFactoryInterface;
use Mailgun\Mailgun;

/**
 * Defines the mailgun factory.
 */
class MailgunFactory {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $mailgunConfig;

  /**
   * Constructs MailgunFactory object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->mailgunConfig = $configFactory->get(MAILGUN_CONFIG_NAME);
  }

  /**
   * Create Mailgun client.
   *
   * @return \Mailgun\Mailgun
   *   Mailgun PHP SDK Client.
   */
  public function create() {
    return Mailgun::create($this->mailgunConfig->get('api_key'));
  }

}
