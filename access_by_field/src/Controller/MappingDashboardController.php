<?php

/**
 * @file
 * Contains \Drupal\access_by_field\Controller\MappingDashboardController.
 */

namespace Drupal\access_by_field\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for fields mapping dashboard.
 *
 * @package Drupal\access_by_field\Controller
 */
class MappingDashboardController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $renderer;

  /**
   * AbfFieldsMappingForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('renderer'),
    );
  }

  /**
   * Get the list of all fields mappings.
   *
   * @return array
   */
  public function getMappingList() {
    // We are preparing a table to display configured data.
    // Table header.
    $header = [
      'mapping_label' => $this->t('Mapping Label'),
      'entity_type' => $this->t('Entity'),
      'entity_bundle' => $this->t('Bundle'),
      'entity_field' => $this->t('Entity Field'),
      'user_field' => $this->t('User Field'),
      'access_level' => $this->t('Access Level'),
      'operations' => $this->t('Operations'),
    ];
    $rows = [];

    // Table rows.
    // Get raw data from mapping configuration.
    $mapping_data = $this->config('abf_fields_mapping.settings')->getRawData();
    foreach ($mapping_data as $bundle => $data) {
      if (empty($data)) {
        continue;
      }
      // Field mapping URL for edit link.
      $links['edit'] = [
        'url' => Url::fromRoute('access_by_field.add_field_mapping', ['type' => $data['entity_type'], 'bundle' => $bundle]),
        'title' => $this->t('Edit'),
      ];
      $links['delete'] = [
        'url' => Url::fromRoute('access_by_field.delete_field_mapping', ['bundle' => $bundle]),
        'title' => $this->t('Delete'),
      ];
      $build_link_action = [
        '#type' => 'dropbutton',
        '#links' => $links,
      ];

      // Get node type label.
      $label = $this->entityTypeBundleInfo->getBundleInfo($data['entity_type'])[$bundle]['label'];
      // Get permission levels set in the configuration.
      $access_level_data = array_filter($data['access_level'], 'ucfirst');

      // Table rows to render content type, field mapped, access level & edit link.
      $rows[] = [
        'mapping_label' => $data['mapping_label'],
        'entity_type' => $data['entity_type'],
        'entity_bundle' => $label,
        'entity_field' => $data['entity_field'],
        'user_field' => $data['user_field'],
        'access_level' => implode(", ", array_keys(array_flip($access_level_data))),
        'operations' => $this->renderer->render($build_link_action),
      ];
    }

    // Return the final data set.
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No mappings found for any content type. Go to fields mapping to create one.'),
    ];
  }
}
