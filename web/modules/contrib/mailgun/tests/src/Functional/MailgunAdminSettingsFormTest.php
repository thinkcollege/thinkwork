<?php

namespace Drupal\Tests\mailgun\Functional;

use Drupal\Core\Url;

/**
 * Tests that all provided admin pages are reachable.
 *
 * @group mailgun
 */
class MailgunAdminSettingsFormTest extends MailgunFunctionalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['mailgun', 'mailgun_test'];

  /**
   * Tests admin pages provided by Mailgun.
   */
  public function testSettingsFormSubmit() {
    $admin_user = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($admin_user);

    $this->drupalGet(Url::fromRoute('mailgun.admin_settings_form'));

    // Test invalid value for API key.
    $this->submitSettingsForm(['api_key' => 'invalid_value'], $this->t("Couldn't connect to the Mailgun API. Please check your API settings."));

    // Test valid but not working API key.
    $this->submitSettingsForm(['api_key' => 'key-1234567890notworkingabcdefghijkl'], $this->t("Couldn't connect to the Mailgun API. Please check your API settings."));

    // Test valid and working API key.
    $this->submitSettingsForm(['api_key' => 'key-1234567890workingabcdefghijklmno'], $this->t('The configuration options have been saved.'));

    // Save additional parameters. Check that all fields available on the form.
    $field_values = [
      'debug_mode' => TRUE,
      'test_mode' => TRUE,
      'use_theme' => FALSE,
      'use_queue' => TRUE,
      'tagging_mailkey' => TRUE,
      'tracking_opens' => 'no',
      'tracking_clicks' => 'yes',
    ];
    $this->submitSettingsForm($field_values, $this->t('The configuration options have been saved.'));

    // Rebuild config values after form submit.
    $this->mailgunConfig = $this->config(MAILGUN_CONFIG_NAME);

    // Test that all field values are stored in configuration.
    foreach ($field_values as $field_name => $field_value) {
      $this->assertEquals($field_value, $this->mailgunConfig->get($field_name));
    }
  }

  /**
   * Submits Mailgun settings form with given values and checks status message.
   */
  private function submitSettingsForm(array $values, $result_message) {
    foreach ($values as $field_name => $field_value) {
      $this->getSession()->getPage()->fillField($field_name, $field_value);
    }
    $this->getSession()->getPage()->pressButton($this->t('Save configuration'));
    $this->assertSession()->pageTextContains($result_message);
  }

}
