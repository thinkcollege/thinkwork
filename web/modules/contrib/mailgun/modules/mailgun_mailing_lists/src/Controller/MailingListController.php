<?php

namespace Drupal\mailgun_mailing_lists\Controller;

use Drupal\Core\Controller\ControllerBase;
use Mailgun\Mailgun;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Mailgun\Exception\HttpClientException;

/**
 * Provides page callbacks for Mailgun Mailing Lists module.
 */
class MailingListController extends ControllerBase {

  /**
   * Mailgun handler.
   *
   * @var \Mailgun\Mailgun
   */
  protected $mailgunClient;

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mailgun.mailgun_client'),
      $container->get('logger.factory')->get('mailgun')
    );
  }

  /**
   * MailingListController constructor.
   *
   * @param \Mailgun\Mailgun $mailgun_client
   *   The Mailgun client.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger instance.
   */
  public function __construct(Mailgun $mailgun_client, LoggerInterface $logger) {
    $this->mailgunClient = $mailgun_client;
    $this->logger = $logger;
  }

  /**
   * Return a list of the mailing list members.
   *
   * @param string $list_address
   *   Mailgun list address.
   *
   * @return array
   *   Page build array.
   */
  public function members($list_address) {
    try {
      $rows = [];
      $members = $this->mailgunClient->mailingList()
        ->member()
        ->index($list_address)
        ->getItems();

      if (!empty($members)) {
        foreach ($members as $member) {
          $rows[] = [
            'address' => $member->getAddress(),
            'subscribed' => $member->isSubscribed() ? $this->t('Yes') : $this->t('No'),
          ];
        }
        return [
          '#theme' => 'table',
          '#rows' => $rows,
          '#header' => [
            $this->t('Address'),
            $this->t('Subscribed'),
          ],
        ];
      }
      else {
        return [
          '#markup' => $this->t('No members yet.'),
        ];
      }
    }
    catch (HttpClientException $e) {
      $message = $this->t('Could not retrieve the members list: @message.', ['@message' => $e->getMessage()]);
      $this->logger->error($message);

      return [
        '#markup' => $message,
      ];
    }
  }

}
