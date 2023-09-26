<?php

namespace Drupal\access_by_field\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AbfMappingDeleteForm extends ConfirmFormBase {

  /**
   * Bundle of the item to delete.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * AbfMappingDeleteForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

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
    $config = $this->configFactory->getEditable('abf_fields_mapping.settings');
    $config->clear($this->bundle)->save();

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
