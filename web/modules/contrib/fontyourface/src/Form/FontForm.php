<?php

namespace Drupal\fontyourface\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Font edit forms.
 *
 * @ingroup fontyourface
 */
class FontForm extends ContentEntityForm {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label Font.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label Font.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.font.canonical', ['font' => $entity->id()]);
  }

}
