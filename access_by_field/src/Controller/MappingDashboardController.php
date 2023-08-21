<?php

/**
 * @file
 * Contains \Drupal\access_by_field\Controller\MappingDashboardController.
 */

namespace Drupal\access_by_field\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Controller for fields mapping dashboard.
 *
 * @package Drupal\access_by_field\Controller
 */
class MappingDashboardController extends ControllerBase {

  use StringTranslationTrait;

  public function getMappingList() {
    global $base_url;
    $mapping_data = $this->config('abf_fields_mapping.settings');
    $rows = [];
    $edit = Html::escape($base_url . '/admin/config/access-by-field/fields-mapping?type=' . $mapping_data->get('content_type'));
    $access_level_data = array_filter($mapping_data->get('access_level'), 'ucfirst');

    if (empty($access_level_data)) {

      $url = Url::fromUri('base:/admin/config/access-by-field/content-configuration');
      $link = Link::fromTextAndUrl($this->t('Content Type Fields Mapping'), $url);

      $warning = $this->t('No mappings found for any content type. Visit @link page to override permissions.',
        ['@link' => $link->toString()]
      );
      return [
        '#type' => 'markup',
        '#markup' => Markup::create("<div class='messages messages--warning'>{$warning}</div>"),
      ];
    }
    foreach ($access_level_data as $access) {
      $access_level[] = $access;
    }
    $row = [
      'content_type' => $mapping_data->get('content_type'),
      'fields_mapped' => $mapping_data->get('user_fields'),
      'access_level' => implode(", ", $access_level),
      'operation' => Markup::create('<div><a href="' . $edit . '">' . $this->t('Edit') . '</a></div>'),
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
      'contexts' => ['user'],
    ];
  }

}
