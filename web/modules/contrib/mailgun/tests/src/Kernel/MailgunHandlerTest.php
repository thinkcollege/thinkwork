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
    $this->assertEqual($mailgun->getDomain('test@domain.com'), 'domain.com');
    $this->assertEqual($mailgun->getDomain('test@mg.domain.com'), 'mg.domain.com');
    $this->assertEqual($mailgun->getDomain('From <test@domain.com>'), 'domain.com');
    $this->assertEqual($mailgun->getDomain('From <test@mg.domain.com>'), 'mg.domain.com');
    $this->assertEqual($mailgun->getDomain('From test@mg.domain.com'), 'mg.domain.com');

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $config_factory->getEditable('mailgun.settings')
      ->set('working_domain', 'mg.domain.com')
      ->save();

    // Otherwise, we should return domain according to config value.
    $this->assertEqual($mailgun->getDomain('test@another.domain.com'), 'mg.domain.com');
  }

}
