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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator.
   */
  public function __construct(Mailgun $mailgun_client, ConfigFactoryInterface $config_factory, LoggerInterface $logger, MessengerInterface $messenger, EmailValidatorInterface $email_validator) {
    $this->mailgunConfig = $config_factory->get(MailgunHandlerInterface::CONFIG_NAME);
    $this->logger = $logger;
    $this->mailgun = $mailgun_client;
    $this->messenger = $messenger;
    $this->emailValidator = $email_validator;
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
            '%to' => $this->getRecipients($mailgunMessage),
          ]
        );
        return FALSE;
      }

      $domain = $this->getDomain($mailgunMessage['from']);
      if ($domain === FALSE) {
        $this->logger->error('Failed to send message from %from to %to. Could not retrieve domain from sender info.',
          [
            '%from' => $mailgunMessage['from'],
            '%to' => $this->getRecipients($mailgunMessage),
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
            '%to' => $this->getRecipients($mailgunMessage),
            '%id' => $response->getId(),
            '%message' => $response->getMessage(),
          ]
        );
      }
      return $response;
    }
    catch (Exception $e) {
      $this->logger->error('Exception occurred while trying to send test email from %from to %to. Error code @code: @message',
        [
          '%from' => $mailgunMessage['from'],
          '%to' => $this->getRecipients($mailgunMessage),
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
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

      // By default, limit is 100 domains, but we want to load all of them.
      if ($result->getTotalCount() > 100) {
        $result = $this->mailgun->domains()->index($result->getTotalCount());
      }

      foreach ($result->getDomains() as $domain) {
        $domains[$domain->getName()] = $domain->getName();
      }
      ksort($domains);
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
  public function getDomain($from) {
    $domain = $this->mailgunConfig->get('working_domain');
    if ($domain !== '_sender') {
      return $domain;
    }

    $emailParser = new EmailParser(new EmailLexer());
    if ($this->emailValidator->isValid($from)) {
      return $emailParser->parse($from)['domain'];
    }

    // Extract the domain from the sender's email address.
    // Use regular expression to check since it could be either a plain email
    // address or in the form "Name <example@example.com>".
    $tokens = (preg_match('/^\s*(.+?)\s*<\s*([^>]+)\s*>$/', $from, $matches) === 1) ? explode('@', $matches[2]) : explode('@', $from);
    return !empty($tokens) ? array_pop($tokens) : FALSE;
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
        $this->messenger->addMessage("Please check your API settings. API key and domain shouldn't be empty.", 'warning');
      }
      return FALSE;
    }

    if (!$this->validateMailgunApiKey($apiKey)) {
      if ($showMessage) {
        $this->messenger->addMessage("Couldn't connect to the Mailgun API. Please check your API settings.", 'warning');
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
      $this->messenger->addMessage('The Mailgun library has not been installed correctly.', 'warning');
    }
    return $libraryStatus;
  }

  /**
   * Returns a list of recipients for error/debug log message.
   *
   * @param array $mailgunMessage
   *   A message array, as described in
   *   https://documentation.mailgun.com/en/latest/api-sending.html#sending.
   *
   * @return string
   *   Recipients list in the following format:
   *   user@test.com, user1@test.com; cc: user2@test.com; bcc: user3@test.com.
   */
  protected function getRecipients(array $mailgunMessage) {
    $recipients = is_array($mailgunMessage['to']) ? implode(', ', $mailgunMessage['to']) : $mailgunMessage['to'];

    // Add all recipients (including 'cc' and 'bcc').
    foreach (['cc', 'bcc'] as $parameter) {
      if (!empty($mailgunMessage[$parameter])) {
        $recipients .= "; {$parameter}: {$mailgunMessage[$parameter]}";
      }
    }
    return $recipients;
  }

}
