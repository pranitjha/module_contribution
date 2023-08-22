<?php

namespace Drupal\access_by_field\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AbfFieldsMappingForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configFactory);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
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
    // Get config data to set the default value of fields.
    $config = $this->config('abf_fields_mapping.settings')->getRawData();
    // Get content type from the query parameter.
    $content_type = \Drupal::request()->query->get('type');

    // User & Content fields.
    $user_fields = $this->getFields('user', 'user');
    $content_fields = $this->getFields('node', $content_type);
    // If there are no fields available for mapping in either of entities,
    // set a warning on mapping page & do not render the form.
    if (empty($user_fields) || empty($content_fields)) {
      $warning = $this->t('No fields found for mapping in either Content or User entity.');
      return [
        '#type' => 'markup',
        '#markup' => Markup::create("<div class='messages messages--warning'>{$warning}</div>"),
      ];
    }
    // Getting content label to be displayed on form.
    $content_type_label = $this->entityTypeManager->getStorage('node_type')->load($content_type)->label();
    // Build form for managing entity fields.
    $form['content_type'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Content Type'),
      '#default_value' => $content_type,
      '#required' => TRUE,
    ];
    $form['access_level'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Access level'),
      '#default_value' => $config[$content_type]['access_level'],
      '#required' => TRUE,
      '#options' => [
        'create' => 'Create',
        'view' => 'View',
        'update' => 'Update',
        'delete' => 'Delete',
      ],
    ];
    $form['content_field'] = [
      '#type' => 'select',
      '#title' => $this->t($content_type_label . ' Fields'),
      '#default_value' => $config[$content_type]['content_field'],
      '#required' => TRUE,
      '#options' => $content_fields,
    ];
    $form['user_field'] = [
      '#type' => 'select',
      '#title' => $this->t('User Account Fields'),
      '#default_value' => $config[$content_type]['user_field'],
      '#required' => TRUE,
      '#options' => $user_fields,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $content_type = $form_state->getValue('content_type');
    $mapping_data = [
      'access_level' => $form_state->getValue('access_level'),
      'content_field' => $form_state->getValue('content_field'),
      'user_field' => $form_state->getValue('user_field'),
    ];
    $this->config('abf_fields_mapping.settings')
      ->set($content_type, $mapping_data)
      ->save();

    parent::submitForm($form, $form_state);

  }

  /**
   * Get list of fields of an entity.
   * @param $entity
   * @param $bundle
   *
   * @return array
   */
  protected function getFields($entity ,$bundle) {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity, $bundle);
    foreach ( $field_definitions as $field_name => $field_definition) {
      // Get list of fields of type entity_reference and textfield.
      if (!empty($field_definition->getTargetBundle()) && ($field_definition->getType() === 'string'
        || $field_definition->getType() === 'entity_reference')) {
        $bundle_fields[$field_name] = $field_definition->label();
      }
    }

    return $bundle_fields;
  }

}
