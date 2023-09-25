<?php

namespace Drupal\access_by_field\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class AbfMappingDeleteForm extends ConfirmFormBase {

  /**
   * Bundle of the item to delete.
   *
   * @var string
   */
  protected $bundle;


  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'abf_mapping_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $bundle = NULL) {
    $this->bundle = $bundle;

    return parent::buildForm($form, $form_state);
  }


  /**
   * @inheritDoc
   */
  public function getQuestion() {
    return $this->t('Do you want to delete mapping for %bundle?', ['%bundle' => $this->bundle]);
  }

  /**
   * @inheritDoc
   */
  public function getCancelUrl() {
    return new Url('access_by_field.mapping_dashboard');
  }



  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('abf_fields_mapping.settings');
    $config->clear($this->bundle)->save();

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
