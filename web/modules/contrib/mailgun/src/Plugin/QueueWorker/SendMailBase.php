<?php

namespace Drupal\mailgun\Plugin\QueueWorker;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\mailgun\MailgunHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base functionality for the SendMail Queue Workers.
 */
class SendMailBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Mailgun config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $mailgunConfig;

  /**
   * Mailgun Logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Mailgun mail handler.
   *
   * @var \Drupal\mailgun\MailgunHandlerInterface
   */
  protected $mailgunHandler;

  /**
   * SendMailBase constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ImmutableConfig $settings, LoggerInterface $logger, MailgunHandlerInterface $mailgun_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailgunConfig = $settings;
    $this->logger = $logger;
    $this->mailgunHandler = $mailgun_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')->get(MailgunHandlerInterface::CONFIG_NAME),
      $container->get('logger.factory')->get('mailgun'),
      $container->get('mailgun.mail_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $result = $this->mailgunHandler->sendMail($data->message);

    if ($this->mailgunConfig->get('debug_mode')) {
      $this->logger->notice('Successfully sent message on CRON from %from to %to.',
        [
          '%from' => $data->message['from'],
          '%to' => $data->message['to'],
        ]
      );
    }

    if (!$result) {
      throw new \Exception('Mailgun: email did not pass through API.');
    }
  }

}
