<?php

/**
 * @file
 * Contains \Drupal\access_by_field_value\Controller\MappingDashboardController.
 */

namespace Drupal\access_by_field_value\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;

/**
 * Controller for fields mapping dashboard.
 *
 * @package Drupal\access_by_field_value\Controller
 */
class MappingDashboardController extends ControllerBase {

  public function getMappingList() {
    global $base_url;
    $mapping_data = $this->config('abf_fields_mapping.settings');

    $rows = [];
    $edit = Html::escape($base_url . '/admin/config/access-by-field/fields-mapping?type=' . $mapping_data->get('content_type'));
    foreach (array_filter($mapping_data->get('access_level'), 'ucfirst') as $access) {
      $access_level[] = $access;
    }
    $row = [
      'content_type' => $mapping_data->get('content_type'),
      'fields_mapped' => $mapping_data->get('user_fields'),
      'access_level' => implode(", ", $access_level),
      'operation' => Markup::create('<div><a href="' . $edit . '">' . t('Edit') . '</a></div>'),
    ];
    $rows[] = $row;

    $header = [
      'content_type' => $this->t('Content Type'),
      'fields_mapped' => $this->t('User Field Mapped'),
      'access_level' => $this->t('Access Level'),
      'operation' => $this->t('Operation'),
    ];

    // Return data in table format.
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

}
