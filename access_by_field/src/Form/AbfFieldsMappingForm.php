<?php

namespace Drupal\access_by_field\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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
   * AdminToolbarToolsSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configFactory);
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
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
    $config = $this->config('abf_fields_mapping.settings');
    // Get content type from the query parameter.
    $content_type = \Drupal::request()->query->get('type');
    // If there are no fields available for mapping in User entity,
    // set a warning on mapping page.
    $user_fields = $this->getFields('user', 'user');
    if (empty($user_fields)) {
      $warning = $this->t('No fields found for mapping in User entity.');
      return [
        '#type' => 'markup',
        '#markup' => Markup::create("<div class='messages messages--warning'>{$warning}</div>"),
      ];
    }
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
      '#default_value' => $config->get('access_level'),
      '#required' => TRUE,
      '#options' => [
        'create' => 'Create',
        'view' => 'View',
        'update' => 'Update',
        'delete' => 'Delete',
      ],
    ];
    $form['content_fields'] = [
      '#type' => 'select',
      '#title' => $this->t(ucfirst($content_type . ' Node Fields')),
      '#default_value' => $config->get('content_fields'),
      '#required' => TRUE,
      '#options' => $this->getFields('node', $content_type),
    ];
    $form['user_fields'] = [
      '#type' => 'select',
      '#title' => $this->t('User Account Fields'),
      '#default_value' => $config->get('user_fields'),
      '#required' => TRUE,
      '#options' => $this->getFields('user', 'user'),
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
    $this->config('abf_fields_mapping.settings')
      ->set('content_type', $form_state->getValue('content_type'))
      ->set('access_level', $form_state->getValue('access_level'))
      ->set('content_fields', $form_state->getValue('content_fields'))
      ->set('user_fields', $form_state->getValue('user_fields'))
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
