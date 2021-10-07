<?php

namespace Drupal\mailgun\Plugin\Mail;

/**
 * Queue the email for sending with Mailgun.
 *
 * @Mail(
 *   id = "mailgun_queue_mail",
 *   label = @Translation("Mailgun mailer (queued)"),
 *   description = @Translation("Sends the message using Mailgun with queue.")
 * )
 */
class MailgunQueueMail extends MailgunMail {

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    // Build and queue the message.
    $mailgun_message = $this->buildMessage($message);
    return $this->queueMessage($mailgun_message);
  }

}
