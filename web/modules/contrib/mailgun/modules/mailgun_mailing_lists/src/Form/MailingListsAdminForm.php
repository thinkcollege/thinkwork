<?php

namespace Drupal\mailgun_mailing_lists\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Mailgun\Mailgun;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mailgun\Exception\HttpClientException;

/**
 * Provides admin form for Mailgun lists management.
 */
class MailingListsAdminForm extends FormBase {

  /**
   * Mailgun handler.
   *
   * @var \Mailgun\Mailgun
   */
  protected $mailgunClient;

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
  public function __construct(Mailgun $mailgunClient) {
    $this->mailgunClient = $mailgunClient;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailgun_mailing_lists_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['create_new_list'] = [
      '#type' => 'details',
      '#title' => $this->t('Create new list'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['create_new_list']['list_address'] = [
      '#title' => $this->t('List address'),
      '#type' => 'email',
      '#required' => TRUE,
      '#description' => $this->t('Enter the new list address'),
    ];
    $form['create_new_list']['list_name'] = [
      '#title' => $this->t('List name'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => $this->t('Enter the new list name'),
    ];
    $form['create_new_list']['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#description' => $this->t('Enter short description'),
    ];
    $form['create_new_list']['access_level'] = [
      '#title' => $this->t('Access Level'),
      '#type' => 'select',
      '#description' => $this->t('Access level for a list'),
      '#options' => [
        'readonly' => $this->t('Read Only'),
        'members' => $this->t('Members'),
        'everyone' => $this->t('Everyone'),
      ],
      '#defaul_value' => 'readonly',
    ];

    $form['create_new_list']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
    ];

    $mailgun = $this->mailgunClient;
    $lists = $mailgun->mailingList()->pages()->getLists();
    $rows = [];
    if (!empty($lists)) {
      foreach ($lists as $list) {
        $rows[] = [
          'address' => $list->getAddress(),
          'name' => $list->getName(),
          'members' => $list->getMembersCount() > 0 ? $this->t('@count (@list)', [
            '@count' => $list->getMembersCount(),
            '@list' => Link::createFromRoute($this->t('list'), 'mailgun_mailing_lists.list', ['list_address' => $list->getAddress()])->toString(),
          ]) : $list->getMembersCount(),
          'description' => $list->getDescription(),
          'access_level' => $list->getAccessLevel(),
          'created' => $list->getCreatedAt()->format('d-m-Y H:i'),
        ];
      }
      $form['lists'] = [
        '#theme' => 'table',
        '#rows' => $rows,
        '#header' => [
          $this->t('Address'),
          $this->t('Name'),
          $this->t('Members'),
          $this->t('Description'),
          $this->t('Access Level'),
          $this->t('Created'),
        ],
      ];
    }
    else {
      $form['lists'] = [
        '#markup' => $this->t('No Mailing lists found.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $address = $form_state->getValue('list_address');
    $lists = $this->mailgunClient->mailingList();
    try {
      if ($lists->show($address)) {
        $form_state->setErrorByName('list_address', $this->t('The list %list already exists.', [
          '%list' => $address,
        ]));
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
    $name = $form_state->getValue('list_name');
    $address = $form_state->getValue('list_address');
    $description = $form_state->getValue('description');
    $description = $description ? $description : $name;

    try {
      $this->mailgunClient->mailingList()->create($address, $name, $description, $form_state->getValue('access_level'));
      $this->messenger()->addMessage($this->t('The list %name has been successfully created.', ['%name' => $name]));
    }
    catch (HttpClientException $e) {
      $this->messenger()->addMessage($this->t('The list could not be created: @message.', [
        '@message' => $e->getMessage(),
      ]), 'error');
    }
  }

}
