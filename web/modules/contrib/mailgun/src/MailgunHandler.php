<?php

namespace Drupal\mailgun;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\EmailParser;
use Psr\Log\LoggerInterface;
use Mailgun\Mailgun;
use Mailgun\Exception;

/**
 * Mail handler to send out an email message array to the Mailgun API.
 */
class MailgunHandler implements MailgunHandlerInterface {

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
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The email validator.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * Constructs a new \Drupal\mailgun\MailHandler object.
   *
   * @param \Mailgun\Mailgun $mailgun_client
   *   Mailgun PHP SDK Object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(Mailgun $mailgun_client, ConfigFactoryInterface $configFactory, LoggerInterface $logger, MessengerInterface $messenger, EmailValidatorInterface $emailValidator) {
    $this->mailgunConfig = $configFactory->get(MAILGUN_CONFIG_NAME);
    $this->logger = $logger;
    $this->mailgun = $mailgun_client;
    $this->messenger = $messenger;
    $this->emailValidator = $emailValidator;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMail(array $mailgunMessage) {
    try {
      if (!$this->validateMailgunApiSettings()) {
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
      $this->logger->error('Exception occurred while trying to send test email from %from to %to. @code: @message @responseBodyMessage.',
        [
          '%from' => $mailgunMessage['from'],
          '%to' => $mailgunMessage['to'],
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
          '@responseBodyMessage' => $e->getResponseBody()['message'],
        ]
      );
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
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
      $this->logger->error('Could not retrieve domains from Mailgun API. @code: @message.',
        [
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ]
      );
    }

    return $domains;
  }

  /**
   * {@inheritdoc}
   */
  private function getDomain($email) {
    $domain = $this->mailgunConfig->get('working_domain');
    if ($domain !== '_sender') {
      return $domain;
    }

    $emailParser = new EmailParser(new EmailLexer());
    if ($this->emailValidator->isValid($email)) {
      return $emailParser->parse($email)['domain'];
    }

    // Extract the domain from the sender's email address.
    // Use regular expression to check since it could be either a plain email
    // address or in the form "Name <example@example.com>".
    $tokens = (preg_match('/^\s*(.+?)\s*<\s*([^>]+)\s*>$/', $email, $matches) === 1) ? explode('@', $matches[2]) : explode('@', $email);
    return array_pop($tokens);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated Scheduled for removal.
   *   Use MailgunHandlerInterface::moduleStatus() service method instead.
   */
  public static function status($showMessage = FALSE) {
    return \Drupal::service('mailgun.mail_handler')
      ->moduleStatus($showMessage);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated Scheduled for removal.
   *   Use MailgunHandlerInterface::validateMailgunLibrary() service method
   *   instead.
   */
  public static function checkLibrary($showMessage = FALSE) {
    return \Drupal::service('mailgun.mail_handler')
      ->validateMailgunLibrary($showMessage);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated Scheduled for removal.
   *   Use MailgunHandlerInterface::validateMailgunApiSettings() service method
   *   instead.
   */
  public static function checkApiSettings($showMessage = FALSE) {
    return \Drupal::service('mailgun.mail_handler')
      ->validateMailgunApiSettings($showMessage);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated Scheduled for removal.
   *   Use MailgunHandlerInterface::validateMailgunApiKey() service method
   *   instead.
   */
  public static function validateKey($key) {
    return \Drupal::service('mailgun.mail_handler')
      ->validateMailgunApiKey($key);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleStatus($showMessage = FALSE) {
    return $this->validateMailgunLibrary($showMessage)
      && $this->validateMailgunApiSettings($showMessage);
  }

  /**
   * {@inheritdoc}
   */
  public function validateMailgunApiKey($key) {
    if (!$this->validateMailgunLibrary()) {
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

  /**
   * {@inheritdoc}
   */
  public function validateMailgunApiSettings($showMessage = FALSE) {
    $apiKey = $this->mailgunConfig->get('api_key');
    $workingDomain = $this->mailgunConfig->get('working_domain');

    if (empty($apiKey) || empty($workingDomain)) {
      if ($showMessage) {
        $this->messenger->addMessage(t("Please check your API settings. API key and domain shouldn't be empty."), 'warning');
      }
      return FALSE;
    }

    if (!$this->validateMailgunApiKey($apiKey)) {
      if ($showMessage) {
        $this->messenger->addMessage(t("Couldn't connect to the Mailgun API. Please check your API settings."), 'warning');
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateMailgunLibrary($showMessage = FALSE) {
    $libraryStatus = class_exists('\Mailgun\Mailgun');
    if ($showMessage === FALSE) {
      return $libraryStatus;
    }

    if ($libraryStatus === FALSE) {
      $this->messenger->addMessage(t('The Mailgun library has not been installed correctly.'), 'warning');
    }
    return $libraryStatus;
  }

}
