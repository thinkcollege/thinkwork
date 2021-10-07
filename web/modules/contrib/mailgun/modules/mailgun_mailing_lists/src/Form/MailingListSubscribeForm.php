<?php

namespace Drupal\mailgun_mailing_lists\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Mailgun\Exception\HttpClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mailgun\Mailgun;

/**
 * Provides list subscription form.
 */
class MailingListSubscribeForm extends FormBase {

  /**
   * Mailgun client.
   *
   * @var \Mailgun\Mailgun
   */
  protected $mailgunClient;

  /**
   * Mailing list address.
   *
   * @var string
   */
  protected $listAddress;

  /**
   * Constructs a new MailingListSubscribeForm object.
   *
   * @param \Mailgun\Mailgun $mailgun_client
   *   The Mailgun client.
   * @param string $list_address
   *   The list address.
   */
  public function __construct(Mailgun $mailgun_client, $list_address = NULL) {
    $this->mailgunClient = $mailgun_client;
    $this->listAddress = $list_address;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mailgun.mailgun_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailgun_mailing_list_subscribe';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    try {
      if ($this->mailgunClient->mailingList()->member()->show($this->listAddress, $email)) {
        $form_state->setErrorByName('name', $this->t("You are already subscribed to this list."));
      }
    }
    catch (HttpClientException $e) {
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    try {
      $this->mailgunClient->mailingList()->member()->create($this->listAddress, $email, $email);
      $this->messenger()->addMessage($this->t("You've successfully subscribed."));
    }
    catch (HttpClientException $e) {
      $this->messenger()->addMessage($this->t("Error occurred. Please try again later."));
    }
  }

}
