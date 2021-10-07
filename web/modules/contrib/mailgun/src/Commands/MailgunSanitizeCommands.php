<?php

namespace Drupal\mailgun\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Queue\QueueFactory;
use Drush\Commands\DrushCommands;
use Drush\Drupal\Commands\sql\SanitizePluginInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Drush sql-sanitize plugin for sanitizing data in the Mailgun queue.
 *
 * @see \Drush\Drupal\Commands\sql\SanitizeSessionsCommands
 */
class MailgunSanitizeCommands extends DrushCommands implements SanitizePluginInterface {

  /**
   * The Mailgun Queue Worker.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $mailgunQueueWorker;

  /**
   * MailgunSanitizeCommands constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory.
   */
  public function __construct(QueueFactory $queueFactory) {
    parent::__construct();
    $this->mailgunQueueWorker = $queueFactory->get('mailgun_send_mail');
  }

  /**
   * Adds 'sanitize-mailgun-queue' to the options list.
   *
   * By default, the queue will be emptied. Please add the following option to
   * prevent that '--sanitize-mailgun-queue=no'.
   *
   * @hook option sql-sanitize
   * @option sanitize-mailgun-queue
   */
  public function options($options = ['sanitize-mailgun-queue' => NULL]) {}

  /**
   * Removes all items from the Mailgun queue.
   *
   * @hook post-command sql-sanitize
   */
  public function sanitize($result, CommandData $command_data) {
    $options = $command_data->options();
    if ($this->applies($options['sanitize-mailgun-queue'])) {
      $this->mailgunQueueWorker->deleteQueue();
      $this->logger()->success(dt('Mailgun queue emptied.'));
    }
  }

  /**
   * Prepares messages.
   *
   * @hook on-event sql-sanitize-confirms
   */
  public function messages(&$messages, InputInterface $input) {
    $options = $input->getOptions();
    if ($this->applies($options['sanitize-mailgun-queue'])) {
      $messages[] = dt('Empty Mailgun queue.');
    }
  }

  /**
   * Verifies that 'sanitize-mailgun-queue' option is not set to "no".
   *
   * @return bool
   *   TRUE if santize realname is enabled.
   */
  protected function applies($value) {
    return ($value !== 'no');
  }

}
