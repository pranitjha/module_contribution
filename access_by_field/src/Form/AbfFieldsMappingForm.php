<?php

namespace Drupal\access_by_field\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
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
  public function __construct(ConfigFactoryInterface $configFactory, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
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
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $bundle = NULL) {
    // Getting data from configuration for setting default value of each field.
    // Some of the configs are mapped directly so skip the process if data is not an array.
    $mapping_data = $this->config('abf_fields_mapping.settings')->getRawData();
    if (!is_array($mapping_data)) {
      return;
    }
    // Build form for managing entity fields and mapping.
    $form['mapping_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mapping Label'),
      '#default_value' => (!empty($type) && !empty($bundle)) ? $mapping_data[$bundle]['mapping_label'] : '',
      '#required' => TRUE,
    ];
    // Allow user to select entity type for which the restrictions are needed.
    // Entities that support the mapping are Content Type, Taxonomy & Media.
    // When user selects an entity type, a form loads with a set of fields
    // to capture the mapping information for that entity.
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#options' => [
        'node' => 'Content Type',
        'media' => 'Media Type',
        'taxonomy_term' => 'Vocabulary'
      ],
      '#default_value' => !empty($type) ? $type : '--none--',
      '#empty_option' => $this->t('- Select an entity type -'),
      '#ajax' => [
        'callback' => '::entityTypeCallback',
        'wrapper' => 'entity-type-container',
        'event' => 'change',
        ],
      '#disabled' => (!empty($type) && !empty($bundle)), // Keep it disabled on edit form.
    ];

    // Check if user has selected an entity type. If not, get value from URL.
    $entity_type_input = $form_state->getValue('entity_type');
    $entity_type = !empty($entity_type_input) ? $entity_type_input : $type;

    // Container to appear when entity type field has data.
    $form['entity_type_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'entity-type-container'],
    ];
    // Keeping the field disabled on edit form so that user does not update
    // this field.
    if (isset($entity_type)) {
      $form['entity_type_container']['entity_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity Bundle'),
        '#required' => TRUE,
        '#options' => $this->getEntityBundles($entity_type),
        '#default_value' => !empty($bundle) ? $bundle : '--none--',
        '#empty_option' => $this->t('- Select entity bundle -'),
        '#ajax' => [
          'callback' => '::entityBundleCallback',
          'wrapper' => 'entity-bundle-container',
          'event' => 'change',
        ],
        '#disabled' => (!empty($type) && !empty($bundle)),
      ];
    }

    // The selection of entity bundle triggers another container that
    // displays field list & access level.
    $form['entity_bundle_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'entity-bundle-container'],
    ];
    // Again if the bundle is selected by user, the value should be taken from
    // form state to capture the data on Add mapping.
    $entity_bundle_input = $form_state->getValue('entity_bundle');
    $entity_bundle = !empty($entity_bundle_input) ? $entity_bundle_input : $bundle;

    if(isset($entity_bundle)) {
      // Getting content label to be displayed on form.
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      // Check is some data is previously set for same set of entity type & bundle.
      // If yes, map them as default value to each field.
      $form['entity_bundle_container']['entity_field'] = [
        '#type' => 'select',
        '#title' => $this->t($bundle_info[$entity_bundle]['label'] . ' Fields'),
        '#required' => TRUE,
        '#default_value' => isset($mapping_data[$entity_bundle]) ? $mapping_data[$entity_bundle]['entity_field'] : '',
        '#options' => $this->getEntityFields($entity_type, $entity_bundle),
      ];
      $form['entity_bundle_container']['user_field'] = [
        '#type' => 'select',
        '#title' => $this->t('User Account Fields'),
        '#default_value' => isset($mapping_data[$entity_bundle]) ? $mapping_data[$entity_bundle]['user_field'] : '',
        '#required' => TRUE,
        '#options' => $this->getEntityFields('user', 'user'),
      ];
      // The access level field decide what is the level of restriction we
      // need for a content type & user role.
      $form['entity_bundle_container']['access_level'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Access level'),
        '#default_value' => isset($mapping_data[$entity_bundle]) ? $mapping_data[$entity_bundle]['access_level'] : [],
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Preparing the config data in a format that each entity will have
    // its mapping info that consists of label, entity type, access level,
    // entity & user fields.
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

    // With each form submission, user will get redirected to the dashboard.
    $url = Url::fromRoute('access_by_field.mapping_dashboard');
    $form_state->setRedirectUrl($url);

    parent::submitForm($form, $form_state);
  }

  /**
   * Handles the AJAX request to load entity type container.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function entityTypeCallback($form, FormStateInterface $form_state) {
    return $form['entity_type_container'];
  }

  /**
   * Handles the AJAX request to load entity bundle container.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function entityBundleCallback($form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    // Skip if mapping data is not an array.
    $mapping_data = $this->config('abf_fields_mapping.settings')->getRawData();
    if (!is_array($mapping_data)) {
      return;
    }
    $selected_entity = $form_state->getValue('entity_type');
    $selected_bundle = $form_state->getValue('entity_bundle');
    // Display message if an entity does not have supported field.
    if (empty($this->getEntityFields($selected_entity ,$selected_bundle))) {
      $message = $this->t('The selected bundle does not have any supported field, please add one.');
      $warning = [
        '#type' => 'markup',
        '#markup' => Markup::create("<div class='messages messages--warning'>{$message}</div>"),
      ];
      $ajax_response->addCommand(new HtmlCommand('#entity-bundle-container', $warning));

      return $ajax_response;
    }
    // If mapping for a bundle exits, ask user to update existing config.
    elseif(array_key_exists($selected_bundle, $mapping_data)) {
      // Get route for existing mapping based on selected entity type & bundle.
      $existing_route = Url::fromRoute('access_by_field.add_field_mapping', [
          'type' => $selected_entity,
          'bundle' => $selected_bundle
        ]);
      $link = Link::fromTextAndUrl($this->t('Existing Field Mapping'), $existing_route);
      $message = $this->t('Mapping exists for ' . $selected_bundle .  '. Visit @link if you need to override it.',
        ['@link' => $link->toString()]
      );
      $warning = [
        '#type' => 'markup',
        '#markup' => Markup::create("<div class='messages messages--warning'>{$message}</div>"),
      ];
      $ajax_response->addCommand(new HtmlCommand('#entity-bundle-container', $warning));

      return $ajax_response;
    }
    // For a new mapping, load container.
    else {
      return $form['entity_bundle_container'];
    }
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
    foreach ($field_definitions as $field_name => $field_definition) {
      // Get list of fields of type entity_reference and boolean.
      if (!empty($field_definition->getTargetBundle()) && ($field_definition->getType() === 'boolean'
          || $field_definition->getType() === 'entity_reference')) {
        $bundle_fields[$field_name] = $field_definition->getLabel();
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
