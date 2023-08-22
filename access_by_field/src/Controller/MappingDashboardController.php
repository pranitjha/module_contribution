<?php

/**
 * @file
 * Contains \Drupal\access_by_field\Controller\MappingDashboardController.
 */

namespace Drupal\access_by_field\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
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
   * AbfFieldsMappingForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
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
      'content_type' => $this->t('Content Type'),
      'content_field' => $this->t('Content Field'),
      'user_field' => $this->t('User Field'),
      'access_level' => $this->t('Access Level'),
      'operation' => $this->t('Operation'),
    ];

    // Table rows.
    // Get raw data from mapping configuration.
    $mapping_data = $this->config('abf_fields_mapping.settings')->getRawData();
    foreach ($mapping_data as $content_type => $mapping) {
      // Get node type label.
      $content_type_label = $this->entityTypeManager->getStorage('node_type')->load($content_type)->label();
      // Field mapping URL for edit link.
      $edit_link = Url::fromRoute('access_by_field.add_field_mapping', ['type' => $content_type], ['absolute' => TRUE]);
      // Get permission levels set in the configuration.
      $access_level_data = array_filter($mapping['access_level'], 'ucfirst');
      // Table rows to render content type, field mapped, access level & edit link.
      $rows[] = [
        'content_type' => $content_type_label,
        'content_field' => $mapping['user_field'],
        'user_field' => $mapping['content_field'],
        'access_level' => implode(", ", array_keys(array_flip($access_level_data))),
        'operation' => Markup::create('<div><a href="' . $edit_link->toString() . '">' . $this->t('Edit') . '</a></div>'),
      ];
    }

    // If no mappings exist, put a message with mapping config link.
    $url = Url::fromUri('base:/admin/config/access-by-field/content-configuration');
    $link = Link::fromTextAndUrl($this->t('Content Type Fields Mapping'), $url);

    // Return the final data set.
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No mappings found for any content type. Visit @link page to create one.',
        ['@link' => $link->toString()]
      ),
    ];
  }
}
