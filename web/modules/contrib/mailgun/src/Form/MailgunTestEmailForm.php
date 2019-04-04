<?php

namespace Drupal\mailgun\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\mailgun\MailgunHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mailgun\MailgunHandlerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class MailgunTestEmailForm.
 *
 * @package Drupal\mailgun\Form
 */
class MailgunTestEmailForm extends FormBase {

  /**
   * Drupal\mailgun\MailgunHandlerInterface definition.
   *
   * @var \Drupal\mailgun\MailgunHandlerInterface
   */
  protected $mailgunHandler;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $user;

  /**
   * Mail Manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * MailgunTestEmailForm constructor.
   */
  public function __construct(MailgunHandlerInterface $mailgunHandler, AccountProxyInterface $user, MailManagerInterface $mailManager, FileSystemInterface $fileSystem, ModuleHandlerInterface $moduleHandler) {
    $this->mailgunHandler = $mailgunHandler;
    $this->user = $user;
    $this->mailManager = $mailManager;
    $this->fileSystem = $fileSystem;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mailgun.mail_handler'),
      $container->get('current_user'),
      $container->get('plugin.manager.mail'),
      $container->get('file_system'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailgun_test_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    MailgunHandler::status(TRUE);

    // Display a warning if Mailgun is not a default mailer.
    $sender = \Drupal::config('mailsystem.settings')->get('defaults.sender');
    if ($sender != 'mailgun_mail') {
      $this->messenger()->addMessage(t('Mailgun is not a default Mailsystem plugin. You may update settings at @link.', [
        '@link' => Link::createFromRoute($this->t('here'), 'mailsystem.settings')->toString()
      ]), 'warning');
    }

    // We can test all mail systems with this form.
    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#required' => TRUE,
      '#description' => $this->t('Email will be sent to this address. You can use commas to separate multiple recipients.'),
      '#default_value' => $this->user->getEmail(),
    ];

    $form['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
      '#default_value' => $this->t('Howdy!

If this e-mail is displayed correctly and delivered sound and safe, congrats! You have successfully configured Mailgun.'),
    ];

    $form['include_attachment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include attachment'),
      '#description' => $this->t('If checked, an image will be included as an attachment with the test e-mail.'),
    ];

    $form['extra'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional parameters'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => $this->t('You may test more parameters to make sure they are working.'),
    ];
    $form['extra']['reply_to'] = [
      '#type' => 'email',
      '#title' => $this->t('Reply-To'),
    ];
    $form['extra']['cc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CC'),
      '#description' => $this->t('You can use commas to separate multiple recipients.'),
    ];
    $form['extra']['bcc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BCC'),
      '#description' => $this->t('You can use commas to separate multiple recipients.'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('mailgun.admin_settings_form'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $to = $form_state->getValue('to');

    $params = [
      'subject' => $this->t('Mailgun works!'),
      'body' => [$form_state->getValue('body')],
    ];

    if (!empty($form_state->getValue('include_attachment'))) {
      $params['attachments'][] = $this->fileSystem->realpath('core/misc/druplicon.png');
    }

    // Add CC / BCC values if they are set.
    if (!empty($cc = $form_state->getValue('cc'))) {
      $params['cc'] = $cc;
    }
    if (!empty($bcc = $form_state->getValue('bcc'))) {
      $params['bcc'] = $bcc;
    }

    $result = $this->mailManager->mail('mailgun', 'test_form_email', $to, $this->user->getPreferredLangcode(), $params, $form_state->getValue('reply_to'), TRUE);

    if ($result['result'] === TRUE) {
      $this->messenger()->addMessage($this->t('Successfully sent message to %to.', ['%to' => $to]));
    }
    else {
      if ($this->moduleHandler->moduleExists('dblog')) {
        $this->messenger()->addMessage($this->t('Something went wrong. Please check @logs for details.', [
          '@logs' => Link::createFromRoute($this->t('logs'), 'dblog.overview')
            ->toString(),
        ]), 'warning');
      }
      else {
        $this->messenger()->addMessage($this->t('Something went wrong. Please check logs for details.'), 'warning');
      }
    }
  }

}
