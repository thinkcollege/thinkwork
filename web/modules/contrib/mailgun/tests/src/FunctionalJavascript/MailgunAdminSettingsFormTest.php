<?php

namespace Drupal\Tests\mailgun\Functional;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\key\Entity\Key;
use Drupal\mailgun\MailgunHandlerInterface;

/**
 * Tests that all provided admin pages are reachable.
 *
 * @group mailgun
 */
class MailgunAdminSettingsFormTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['mailgun', 'mailgun_test'];

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

  /**
   * Tests admin pages provided by Mailgun.
   */
  public function testSettingsFormSubmit() {
    $this->getSession()->resizeWindow(1200, 2000);

    $admin_user = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($admin_user);

    $this->drupalGet(Url::fromRoute('mailgun.admin_settings_form'));

    // Make sure that "API Key Storage" field is visible and required.
    $api_key_field = $this->assertSession()->elementExists('css', 'select[name="api_key_storage"]');
    $this->assertTrue($api_key_field->hasAttribute('required'));

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
    $this->getSession()->getPage()->find('css', '#edit-advanced-settings summary')->click();
    $this->submitSettingsForm($field_values, 'The configuration options have been saved.');

    // Rebuild config values after form submit.
    $this->mailgunConfig = $this->config(MailgunHandlerInterface::CONFIG_NAME);

    // Test that all field values are stored in configuration.
    foreach ($field_values as $field_name => $field_value) {
      $this->assertEquals($field_value, $this->mailgunConfig->get($field_name));
    }
  }

  /**
   * Tests Key module integration.
   */
  public function testKeyIntegration() {
    $page = $this->getSession()->getPage();

    \Drupal::service('module_installer')
      ->install(['key']);

    // Create a key with a faux valid key.
    $key = Key::create([
      'id' => 'mailgun_api_key',
      'label' => 'Mailgun API Key',
      'key_type' => 'authentication',
      'key_provider' => 'config',
      'key_provider_settings' => [
        'key_value' => 'key-1234567890workingabcdefghijklmno',
      ],
      'key_input' => 'text_field',
    ]);
    $key->save();

    $admin_user = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($admin_user);

    $this->drupalGet(Url::fromRoute('mailgun.admin_settings_form'));

    $field = $this->assertSession()->elementExists('css', 'input[name="api_key"]');
    $this->assertTrue($field->isVisible());
    $field = $this->assertSession()->elementExists('css', 'select[name="api_key_key"]');
    $this->assertFalse($field->isVisible());

    $page->fillField('api_key_storage', 'key');

    $field = $this->assertSession()->elementExists('css', 'input[name="api_key"]');
    $this->assertFalse($field->isVisible());
    $field = $this->assertSession()->elementExists('css', 'select[name="api_key_key"]');
    $this->assertTrue($field->isVisible());
    $this->assertTrue($field->hasAttribute('required'));
    $this->assertEquals($field->getValue(), 'mailgun_api_key');

    $form_values = [
      'api_key_storage' => 'key',
      'api_key_key' => 'mailgun_api_key',
    ];

    // Test valid and working API key.
    $this->submitSettingsForm($form_values, 'The configuration options have been saved.');

    // Delete key and replace with invalid key.
    $key->delete();
    $key = Key::create([
      'id' => 'mailgun_api_key',
      'label' => 'Mailgun API Key',
      'key_type' => 'authentication',
      'key_provider' => 'config',
      'key_provider_settings' => [
        'key_value' => 'invalid_value',
      ],
      'key_input' => 'text_field',
    ]);
    $key->save();

    // Test invalid value for API key.
    $this->submitSettingsForm($form_values, "Couldn't connect to the Mailgun API. Please check your API settings.");

    // Delete key and replace with valid key but not working API key.
    $key->delete();
    $key = Key::create([
      'id' => 'mailgun_api_key',
      'label' => 'Mailgun API Key',
      'key_type' => 'authentication',
      'key_provider' => 'config',
      'key_provider_settings' => [
        'key_value' => 'key-1234567890notworkingabcdefghijkl',
      ],
      'key_input' => 'text_field',
    ]);
    $key->save();

    // Test valid but not working API key.
    $this->submitSettingsForm($form_values, "Couldn't connect to the Mailgun API. Please check your API settings.");
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
