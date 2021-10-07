<?php

namespace Drupal\Tests\mailgun\Kernel;

use Drupal\mailgun\MailgunFactory;
use Mailgun\Mailgun;

/**
 * Mailgun client factory test.
 *
 * @coversDefaultClass \Drupal\mailgun\MailgunFactory
 *
 * @group mailgun
 */
class MailgunFactoryTest extends MailgunKernelTestBase {

  /**
   * Make sure the client factory returns a client object.
   */
  public function testCreate() {
    $factory = $this->container->get('mailgun.mailgun_client_factory');
    $this->assertInstanceOf(MailgunFactory::class, $factory);
    $this->assertInstanceOf(Mailgun::class, $factory->create());
  }

  /**
   * Make sure the client may be retrieved as a service.
   */
  public function testClientService() {
    $api_key = 'test';
    $this->setConfigValue('api_key', $api_key);
    $mailgun = $this->container->get('mailgun.mailgun_client');
    $this->assertInstanceOf(Mailgun::class, $mailgun);
  }

}
