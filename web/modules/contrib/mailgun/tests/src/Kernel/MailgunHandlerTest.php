<?php

namespace Drupal\Tests\mailgun\Kernel;

/**
 * Mailgun handler service test.
 *
 * @group mailgun
 */
class MailgunHandlerTest extends MailgunKernelTestBase {

  /**
   * Make sure we return correct domain.
   */
  public function testGetDomainFunction() {
    /** @var \Drupal\mailgun\MailgunHandlerInterface $mailgun */
    $mailgun = $this->container->get('mailgun.mail_handler');

    // By default, we should parse domain based on "From" value.
    $this->assertEquals('domain.com', $mailgun->getDomain('test@domain.com'));
    $this->assertEquals('mg.domain.com', $mailgun->getDomain('test@mg.domain.com'));
    $this->assertEquals('domain.com', $mailgun->getDomain('From <test@domain.com>'));
    $this->assertEquals('mg.domain.com', $mailgun->getDomain('From <test@mg.domain.com>'));
    $this->assertEquals('mg.domain.com', $mailgun->getDomain('From test@mg.domain.com'));

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $config_factory->getEditable('mailgun.settings')
      ->set('working_domain', 'mg.domain.com')
      ->save();

    // Otherwise, we should return domain according to config value.
    $this->assertEquals('mg.domain.com', $mailgun->getDomain('test@another.domain.com'));
  }

}
