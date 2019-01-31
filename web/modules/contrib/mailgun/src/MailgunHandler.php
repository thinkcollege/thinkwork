<?php

namespace Drupal\mailgun;

use Drupal\Core\Config\ConfigFactoryInterface;
use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\EmailParser;
use Egulias\EmailValidator\EmailValidator;
use Psr\Log\LoggerInterface;
use Mailgun\Mailgun;
use Mailgun\Exception;

/**
 * Mail handler to send out an email message array to the Mailgun API.
 */
class MailgunHandler {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $mailgunConfig;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Mailgun client.
   *
   * @var \Mailgun\Mailgun
   */
  protected $mailgun;

  /**
   * Constructs a new \Drupal\mailgun\MailHandler object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerInterface $logger) {
    $this->mailgunConfig = $configFactory->get(MAILGUN_CONFIG_NAME);
    $this->logger = $logger;
    $this->mailgun = Mailgun::create($this->mailgunConfig->get('api_key'));
  }

  /**
   * Connects to Mailgun API and sends out the email.
   *
   * @param array $mailgunMessage
   *   A message array, as described in
   *   https://documentation.mailgun.com/en/latest/api-sending.html#sending.
   *
   * @return bool
   *   TRUE if the mail was successfully accepted by the API, FALSE otherwise.
   *
   * @see https://documentation.mailgun.com/en/latest/api-sending.html#sending
   */
  public function sendMail(array $mailgunMessage) {
    try {
      if (self::checkApiSettings() === FALSE) {
        $this->logger->error('Failed to send message from %from to %to. Please check the Mailgun settings.',
          [
            '%from' => $mailgunMessage['from'],
            '%to' => $mailgunMessage['to'],
          ]
        );
        return FALSE;
      }

      $domain = $this->getDomain($mailgunMessage['from']);
      if ($domain === FALSE) {
        $this->logger->error('Failed to send message from %from to %to. Could not retrieve domain from sender info.',
          [
            '%from' => $mailgunMessage['from'],
            '%to' => $mailgunMessage['to'],
          ]
        );
        return FALSE;
      }

      $response = $this->mailgun->messages()->send($domain, $mailgunMessage);

      // Debug mode: log all messages.
      if ($this->mailgunConfig->get('debug_mode')) {
        $this->logger->notice('Successfully sent message from %from to %to. %id %message.',
          [
            '%from' => $mailgunMessage['from'],
            '%to' => $mailgunMessage['to'],
            '%id' => $response->getId(),
            '%message' => $response->getMessage(),
          ]
        );
      }
      return TRUE;
    }
    catch (Exception $e) {
      $this->logger->error('Exception occurred while trying to send test email from %from to %to. @code: @message.',
        [
          '%from' => $mailgunMessage['from'],
          '%to' => $mailgunMessage['to'],
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ]
      );
      return FALSE;
    }
  }

  /**
   * Get domains list from API.
   */
  public function getDomains() {
    $domains = [];
    try {
      $result = $this->mailgun->domains()->index();
      foreach ($result->getDomains() as $domain) {
        $domains[$domain->getName()] = $domain->getName();
      }
    }
    catch (Exception $e) {
      $this->logger->error('Could not retrieve domains from Mailgun API. @code: @message.', [
        '@code' => $e->getCode(),
        '@message' => $e->getMessage(),
      ]);
    }

    return $domains;
  }

  /**
   * Get working domain for the message.
   */
  private function getDomain($email) {
    $domain = $this->mailgunConfig->get('working_domain');
    if ($domain !== '_sender') {
      return $domain;
    }

    $emailParser = new EmailParser(new EmailLexer());
    $emailValidator = new EmailValidator();

    if ($emailValidator->isValid($email) === TRUE) {
      return $emailParser->parse($email)['domain'];
    }

    // Extract the domain from the sender's email address.
    // Use regular expression to check since it could be either a plain email
    // address or in the form "Name <example@example.com>".
    $tokens = (preg_match('/^\s*(.+?)\s*<\s*([^>]+)\s*>$/', $email, $matches) === 1) ? explode('@', $matches[2]) : explode('@', $email);
    return array_pop($tokens);
  }

  /**
   * Check Mailgun library and API settings.
   */
  public static function status($showMessage = FALSE) {
    return self::checkLibrary($showMessage) && self::checkApiSettings($showMessage);
  }

  /**
   * Check that Mailgun PHP SDK is installed correctly.
   */
  public static function checkLibrary($showMessage = FALSE) {
    $libraryStatus = class_exists('\Mailgun\Mailgun');
    if ($showMessage === FALSE) {
      return $libraryStatus;
    }

    if ($libraryStatus === FALSE) {
      drupal_set_message(t('The Mailgun library has not been installed correctly.'), 'warning');
    }
    return $libraryStatus;
  }

  /**
   * Check if API settings are correct and not empty.
   */
  public static function checkApiSettings($showMessage = FALSE) {
    $mailgunSettings = \Drupal::config(MAILGUN_CONFIG_NAME);
    $apiKey = $mailgunSettings->get('api_key');
    $workingDomain = $mailgunSettings->get('working_domain');

    if (empty($apiKey) || empty($workingDomain)) {
      if ($showMessage) {
        drupal_set_message(t("Please check your API settings. API key and domain shouldn't be empty."), 'warning');
      }
      return FALSE;
    }

    if (self::validateKey($apiKey) === FALSE) {
      if ($showMessage) {
        drupal_set_message(t("Couldn't connect to the Mailgun API. Please check your API settings."), 'warning');
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validates Mailgun API key.
   */
  public static function validateKey($key) {
    if (self::checkLibrary() === FALSE) {
      return FALSE;
    }
    $mailgun = Mailgun::create($key);

    try {
      $mailgun->domains()->index();
    }
    catch (Exception $e) {
      return FALSE;
    }
    return TRUE;
  }

}
