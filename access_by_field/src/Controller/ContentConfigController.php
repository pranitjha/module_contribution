<?php

/**
 * @file
 * Contains \Drupal\access_by_field\Controller\ContentConfigController.
 */

namespace Drupal\access_by_field\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Url;

/**
 * Controller for fields mapping dashboard.
 *
 * @package Drupal\access_by_field\Controller
 */
class ContentConfigController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Renders a table of content types with mapping configuration link.
   *
   * @return array
   */
  public function contentMappingList() {
    $rows = [];
    foreach (NodeType::loadMultiple() as $node_type) {
      // Prepare field mapping URL for each content type.
      $url = Url::fromRoute('access_by_field.add_field_mapping', ['type' => $node_type->id()], ['absolute' => TRUE]);
      // The field mapping URL will be rendered as configure link against
      // each content type, on click of which, user will be taken to
      // the entity field mapping form.
      $row = [
        'content_type' => $node_type->label(),
        'operation' => Markup::create('<div><a href="' . $url->toString() . '">' . $this->t('Configure') . '</a></div>'),
      ];
      $rows[] = $row;
    }

    // Rendering the dataset as a table.
    return [
      '#type' => 'table',
      '#header' => [$this->t('Content Type'), $this->t('Operations')],
      '#rows' => $rows,
    ];
  }

}
