<?php

namespace Drupal\mailgun_mailing_lists\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mailgun\Mailgun;
use Drupal\mailgun_mailing_lists\Form\MailingListSubscribeForm as MailingListSubscribeForm;

/**
 * Provides a 'MailingListSubscribeBlock' block.
 *
 * @Block(
 *  id = "mailing_list_subscribe",
 *  admin_label = @Translation("Mailing list subscribe form"),
 * )
 */
class MailingListSubscribeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Mailgun client.
   *
   * @var \Mailgun\Mailgun
   */
  protected $mailgunClient;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('mailgun.mailgun_client'),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a new MailingListSubscribeBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Mailgun\Mailgun $mailgun_client
   *   The Mailgun client.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Mailgun $mailgun_client, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailgunClient = $mailgun_client;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $form = new MailingListSubscribeForm($this->mailgunClient, $config['mailing_list']);
    return $this->formBuilder->getForm($form);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $config = $this->getConfiguration();
    if (!empty($config['mailing_list'])) {
      $name = $this->mailgunClient->mailingList()->show($config['mailing_list'])->getList()->getName();
      return $this->t('Subscribe to @name', ['@name' => $name]);
    }
    return parent::label();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $lists = $this->mailgunClient->mailingList()->pages()->getLists();
    $options = ['' => $this->t('- None -')];
    foreach ((array) $lists as $list) {
      $options[$list->getAddress()] = $list->getName();
    }
    $form['mailing_list'] = [
      '#type' => 'select',
      '#title' => $this->t('Mailing list'),
      '#options' => $options,
      '#default_value' => isset($config['mailing_list']) ? $config['mailing_list'] : '',
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['mailing_list'] = $form_state->getValue('mailing_list');
  }

}
