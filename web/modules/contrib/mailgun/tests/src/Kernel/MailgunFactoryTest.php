<?php

namespace Drupal\Tests\mailgun\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\mailgun\MailgunFactory;
use Mailgun\Mailgun;

/**
 * Mailgun client factory test.
 *
 * @coversDefaultClass \Drupal\mailgun\MailgunFactory
 *
 * @group Client
 */
class MailgunFactoryTest extends KernelTestBase {

  protected static $modules = ['mailgun'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['mailgun']);
  }

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
    $this->setApiKey($api_key);
    $mailgun = $this->container->get('mailgun.mailgun_client');
    $this->assertInstanceOf(Mailgun::class, $mailgun);
  }

  /**
   * Sets the API key configuration value.
   *
   * @param string $api_key
   *   The API key string.
   */
  protected function setApiKey($api_key) {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $config_factory->getEditable('mailgun.settings')
      ->set('api_key', $api_key)
      ->save();
  }

}
