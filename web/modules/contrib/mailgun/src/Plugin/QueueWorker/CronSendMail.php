<?php

namespace Drupal\mailgun\Plugin\QueueWorker;

/**
 * Sending mails on CRON run.
 *
 * @QueueWorker(
 *   id = "mailgun_send_mail",
 *   title = @Translation("Mailgun Cron Worker"),
 *   cron = {"time" = 10}
 * )
 */
class CronSendMail extends SendMailBase {}
