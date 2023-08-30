<?php

namespace Drupal\access_by_field\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AbfFieldsMappingForm.
 *
 * Config form for mapping fields.
 * @package Drupal\access_by_field\Form
 */
class AbfFieldsMappingForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * AbfFieldsMappingForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   */
  public function __construct(ConfigFactoryInterface $configFactory,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configFactory);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
    );
  }

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return [
      'abf_fields_mapping.settings',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'abf_fields_mapping_settings';
  }
  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Build form for managing entity fields.
    $form['mapping_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mapping Label'),
      '#required' => TRUE,
    ];
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#options' => [
        'node' => 'Content Type',
        'media' => 'Media Type',
        'taxonomy_term' => 'Vocabulary'
      ],
      '#empty_option' => $this->t('- Select an entity type -'),
      '#ajax' => [
        'callback' => '::entityTypeCallback',
        'wrapper' => 'entity-type-container',
        'event' => 'change',
        ]
    ];

    $form['entity_type_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'entity-type-container'],
    ];

    $entity_type = $form_state->getValue('entity_type');
    if (isset($entity_type)) {
      $form['entity_type_container']['entity_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity Bundle'),
        '#required' => TRUE,
        '#options' => $this->getEntityBundles($form_state->getValue('entity_type')),
        '#empty_option' => $this->t('- Select entity bundle -'),
        '#ajax' => [
          'callback' => '::entityBundleCallback',
          'wrapper' => 'entity-bundle-container',
          'event' => 'change',
        ]
      ];
    }

    $form['entity_bundle_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'entity-bundle-container'],
    ];
    $entity_bundle = $form_state->getValue('entity_bundle');
    if(isset($entity_bundle)) {
      // Getting content label to be displayed on form.
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);

      $form['entity_bundle_container']['entity_field'] = [
        '#type' => 'select',
        '#title' => $this->t($bundle_info[$entity_bundle]['label'] . ' Fields'),
        '#required' => TRUE,
        '#options' => $this->getEntityFields($entity_type, $entity_bundle),
      ];
      $form['entity_bundle_container']['user_field'] = [
        '#type' => 'select',
        '#title' => $this->t('User Account Fields'),
        '#required' => TRUE,
        '#options' => $this->getEntityFields('user', 'user'),
      ];
      $form['entity_bundle_container']['access_level'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Access level'),
        '#required' => TRUE,
        '#options' => [
          'create' => 'Create',
          'view' => 'View',
          'update' => 'Update',
          'delete' => 'Delete',
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $form_state->getValue('entity_bundle');
    $mapping_data = [
      'entity_type' => $form_state->getValue('entity_type'),
      'mapping_label' => $form_state->getValue('mapping_label'),
      'access_level' => $form_state->getValue('access_level'),
      'entity_field' => $form_state->getValue('entity_field'),
      'user_field' => $form_state->getValue('user_field'),
    ];
    $this->config('abf_fields_mapping.settings')
      ->set($entity, $mapping_data)
      ->set('entity_type',$form_state->getValue('entity_type'))
      ->set('entity_bundle',$form_state->getValue('entity_bundle'))
      ->save();

    $url = Url::fromRoute('access_by_field.mapping_dashboard');
    $form_state->setRedirectUrl($url);
    parent::submitForm($form, $form_state);

  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function entityTypeCallback($form, FormStateInterface $form_state) {
    return $form['entity_type_container'];
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function entityBundleCallback($form, FormStateInterface $form_state) {
    return $form['entity_bundle_container'];
  }

  /**
   * Get list of fields of selected entity bundle.
   * @param $entity
   * @param $bundle
   *
   * @return array
   */
  protected function getEntityFields($entity ,$bundle): array {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity, $bundle);
    $bundle_fields = [];
    foreach ( $field_definitions as $field_name => $field_definition) {
      // Get list of fields of type entity_reference and boolean.
      if (!empty($field_definition->getTargetBundle()) && ($field_definition->getType() === 'boolean'
          || $field_definition->getType() === 'entity_reference')) {
        $bundle_fields[$field_name] = $field_definition->label();
      }
    }

    return $bundle_fields;
  }

  /**
   * Provides bundle list for an entity type.
   * @param $entity_type
   *
   * @return array
   */
  protected function getEntityBundles($entity_type): array {
    $bundles = [];
    $entity_bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
    foreach ($entity_bundles as $id => $info) {
      $bundles[$id] = $info['label'];
    }

    return $bundles;
  }
}
