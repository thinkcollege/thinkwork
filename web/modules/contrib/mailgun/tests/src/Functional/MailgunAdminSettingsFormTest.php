<?php

namespace Drupal\Tests\mailgun\Functional;

use Drupal\Core\Url;
use Drupal\mailgun\MailgunHandlerInterface;

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

    // Make sure that "API Key" field is visible and required.
    $api_key_field = $this->assertSession()->elementExists('css', 'input[name="api_key"]');
    $this->assertTrue($api_key_field->hasAttribute('required'));

    // Other fields (i.e. "Mailgun Region") should be hidden.
    $this->assertSession()->elementNotExists('css', 'input[name="api_endpoint"]');

    // Test invalid value for API key.
    $this->submitSettingsForm(['api_key' => 'invalid_value'], "Couldn't connect to the Mailgun API. Please check your API settings.");

    // Test valid but not working API key.
    $this->submitSettingsForm(['api_key' => 'key-1234567890notworkingabcdefghijkl'], "Couldn't connect to the Mailgun API. Please check your API settings.");

    // Test valid and working API key.
    $this->submitSettingsForm(['api_key' => 'key-1234567890workingabcdefghijklmno'], 'The configuration options have been saved.');

    // Save additional parameters. Check that all fields available on the form.
    $field_values = [
      'api_endpoint' => 'https://api.eu.mailgun.net',
      'debug_mode' => TRUE,
      'test_mode' => TRUE,
      'use_theme' => FALSE,
      'use_queue' => TRUE,
      'tagging_mailkey' => TRUE,
      'tracking_opens' => 'no',
      'tracking_clicks' => 'yes',
    ];
    $this->submitSettingsForm($field_values, 'The configuration options have been saved.');

    // Rebuild config values after form submit.
    $this->mailgunConfig = $this->config(MailgunHandlerInterface::CONFIG_NAME);

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
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains($result_message);
  }

}
