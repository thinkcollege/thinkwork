<?php

namespace Drupal\Tests\mailgun\Functional;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests that all provided admin pages are reachable.
 *
 * @group mailgun
 */
class MailgunUiPageTest extends MailgunFunctionalTestBase {

  /**
   * List of Mailgun admin routes.
   *
   * @var array
   */
  private $adminPages = [
    'mailgun.admin_settings_form',
    'mailgun.test_email_form',
  ];

  /**
   * Tests admin pages provided by Mailgun.
   */
  public function testAdminPages() {
    $admin_user = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($admin_user);

    // User with 'administer mailgun' permission should have an access.
    $this->checkRoutesStatusCode(Response::HTTP_OK);

    $this->drupalLogout();

    $common_user = $this->drupalCreateUser();
    $this->drupalLogin($common_user);

    // User without 'administer mailgun' permission shouldn't have an access.
    $this->checkRoutesStatusCode(Response::HTTP_FORBIDDEN);
  }

  /**
   * Helper. Checks status codes on admin routes by current user.
   */
  private function checkRoutesStatusCode($status_code) {
    foreach ($this->adminPages as $route) {
      $this->drupalGet(Url::fromRoute($route));
      $this->assertSession()->statusCodeEquals($status_code);
    }
  }

}
