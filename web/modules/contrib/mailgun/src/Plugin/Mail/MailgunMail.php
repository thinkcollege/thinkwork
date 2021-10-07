<?php

namespace Drupal\mailgun\Plugin\Mail;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Render\RendererInterface;
use Drupal\mailgun\MailgunHandlerInterface;
use Html2Text\Html2Text;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default Mailgun mail system plugin.
 *
 * @Mail(
 *   id = "mailgun_mail",
 *   label = @Translation("Mailgun mailer"),
 *   description = @Translation("Sends the message using Mailgun.")
 * )
 */
class MailgunMail implements MailInterface, ContainerFactoryPluginInterface {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $mailgunConfig;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Mailgun handler.
   *
   * @var \Drupal\mailgun\MailgunHandlerInterface
   */
  protected $mailgunHandler;

  /**
   * MailgunMail constructor.
   */
  public function __construct(ImmutableConfig $settings, LoggerInterface $logger, RendererInterface $renderer, QueueFactory $queueFactory, MailgunHandlerInterface $mailgunHandler) {
    $this->mailgunConfig = $settings;
    $this->logger = $logger;
    $this->renderer = $renderer;
    $this->queueFactory = $queueFactory;
    $this->mailgunHandler = $mailgunHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('config.factory')->get(MailgunHandlerInterface::CONFIG_NAME),
      $container->get('logger.factory')->get('mailgun'),
      $container->get('renderer'),
      $container->get('queue'),
      $container->get('mailgun.mail_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // Join the body array into one string.
    if (is_array($message['body'])) {
      $message['body'] = implode("\n\n", $message['body']);
    }

    // If text format is specified in settings, run the message through it.
    $format = $this->mailgunConfig->get('format_filter');
    if (!empty($format)) {
      $message['body'] = check_markup($message['body'], $format, $message['langcode']);
    }

    // Skip theme formatting if the message does not support HTML.
    if (isset($message['params']['html']) && !$message['params']['html']) {
      return $message;
    }

    // Wrap body with theme function.
    if ($this->mailgunConfig->get('use_theme')) {
      $render = [
        '#theme' => isset($message['params']['theme']) ? $message['params']['theme'] : 'mailgun',
        '#message' => $message,
      ];
      $message['body'] = $this->renderer->renderPlain($render);
    }

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    $mailgun_message = $this->buildMessage($message);

    if ($this->mailgunConfig->get('use_queue')) {
      return $this->queueMessage($mailgun_message);
    }
    return $this->mailgunHandler->sendMail($mailgun_message);
  }

  /**
   * Queue a message for sending.
   *
   * @param array $message
   *   Mailgun message array that was build and ready for sending.
   *
   * @return bool
   *   TRUE if the message was queued, otherwise FALSE.
   */
  public function queueMessage(array $message) {
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->queueFactory->get('mailgun_send_mail');

    $item = new \stdClass();
    $item->message = $message;
    $result = $queue->createItem($item);

    if ($result !== FALSE) {
      // Debug mode: log all messages.
      if ($this->mailgunConfig->get('debug_mode')) {
        $this->logger->notice('Successfully queued message from %from to %to.', [
          '%from' => $message['from'],
          '%to' => $message['to'],
        ]);
      }
    }
    else {
      $this->logger->error('Unable to queue message from %from to %to.', [
        '%from' => $message['from'],
        '%to' => $message['to'],
      ]);
    }

    return !empty($result);
  }

  /**
   * Builds the e-mail message in preparation to be sent to Mailgun.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *   $message['params'] may contain additional parameters.
   *
   * @return array
   *   An email array formatted for Mailgun delivery.
   *
   * @see https://documentation.mailgun.com/en/latest/api-sending.html#sending
   */
  protected function buildMessage(array $message) {
    // Build the Mailgun message array.
    $mailgun_message = [
      'from' => $message['headers']['From'],
      'to' => $message['to'],
      'subject' => $message['subject'],
      'html' => $message['body'],
    ];

    // Remove HTML version if the message does not support HTML.
    if (isset($message['params']['html']) && !$message['params']['html']) {
      unset($mailgun_message['html']);
    }

    // Set text version of the message.
    if (isset($message['plain'])) {
      $mailgun_message['text'] = $message['plain'];
    }
    else {
      $converter = new Html2Text($message['body'], ['width' => 0]);
      $mailgun_message['text'] = $converter->getText();
    }

    // Add Cc / Bcc headers.
    if (!empty($message['headers']['Cc'])) {
      $mailgun_message['cc'] = $message['headers']['Cc'];
    }
    if (!empty($message['headers']['Bcc'])) {
      $mailgun_message['bcc'] = $message['headers']['Bcc'];
    }

    // Add Reply-To as header according to Mailgun API.
    if (!empty($message['reply-to'])) {
      $mailgun_message['h:Reply-To'] = $message['reply-to'];
    }

    // For a full list of allowed parameters,
    // see: https://documentation.mailgun.com/api-sending.html#sending.
    $allowed_params = [
      'o:tag',
      'o:campaign',
      'o:deliverytime',
      'o:dkim',
      'o:testmode',
      'o:tracking',
      'o:tracking-clicks',
      'o:tracking-opens',
    ];

    foreach ($message['params'] as $key => $value) {
      // Check if it's one of the known parameters.
      $allowed = (in_array($key, $allowed_params)) ? TRUE : FALSE;

      if ($allowed) {
        $mailgun_message[$key] = $value;
      }
      // Check for custom MIME headers or custom JSON data.
      if (substr($key, 0, 2) == 'h:' || substr($key, 0, 2) == 'v:') {
        $mailgun_message[$key] = $value;
      }
    }

    // Mailgun will accept the message but will not send it.
    if ($this->mailgunConfig->get('test_mode')) {
      $mailgun_message['o:testmode'] = 'yes';
    }

    // Add default tags by mail key if enabled.
    if ($this->mailgunConfig->get('tagging_mailkey')) {
      $mailgun_message['o:tag'][] = $message['id'];
    }

    // Make sure the files provided in the attachments array exist.
    if (!empty($message['params']['attachments'])) {
      $attachments = [];
      foreach ($message['params']['attachments'] as $attachment) {
        if (!empty($attachment['filepath']) && file_exists($attachment['filepath'])) {
          $attachments[] = ['filePath' => $attachment['filepath']];
        }
        elseif (!empty($attachment['filecontent']) && !empty($attachment['filename'])) {
          $attachments[] = [
            'fileContent' => $attachment['filecontent'],
            'filename' => $attachment['filename'],
          ];
        }
      }

      if (count($attachments) > 0) {
        $mailgun_message['attachment'] = $attachments;
      }
    }

    if ($this->checkTracking($message)) {
      $track_opens = $this->mailgunConfig->get('tracking_opens');
      if (!empty($track_opens)) {
        $mailgun_message['o:tracking-opens'] = $track_opens;
      }
      $track_clicks = $this->mailgunConfig->get('tracking_clicks');
      if (!empty($track_clicks)) {
        $mailgun_message['o:tracking-clicks'] = $track_opens;
      }
    }
    else {
      $mailgun_message['o:tracking'] = 'no';
    }

    return $mailgun_message;
  }

  /**
   * Checks, if the mail key is excempted from tracking.
   *
   * @param array $message
   *   A message array.
   *
   * @return bool
   *   TRUE if the tracking is allowed, otherwise FALSE.
   */
  protected function checkTracking(array $message) {
    $tracking = TRUE;
    $exceptions = $this->mailgunConfig->get('tracking_exception');
    if (!empty($exceptions)) {
      $exceptions = str_replace(["\r\n", "\r"], "\n", $exceptions);
      $tracking = !in_array($message['module'] . ':' . $message['key'], explode("\n", $exceptions));
    }
    return $tracking;
  }

}
