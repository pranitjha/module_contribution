<?php

namespace Drupal\access_by_field\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * Class AbfContentConfigForm.
 *
 * The form for managing fields mapping for each content type.
 * @package Drupal\access_by_field\Form
 */
class AbfContentConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'abf_content_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $rows = [];
    $node_types = \Drupal\node\Entity\NodeType::loadMultiple();
    $content_config_url = Url::fromUri('route:access_by_field.content_configuration');

    $rows = [];
    foreach ($node_types as $node_type) {
      $edit = Html::escape($base_url . '/admin/config/access-by-field/fields-mapping?type=' . $node_type->id());
      $row = [];
      $row[] = $node_type->label();
      $row[] = Markup::create('<div><a href="' . $edit . '">' . t('Configure') . '</a></div>');
      $rows[] = $row;
    }

    $form['data'] = [
      '#type' => 'table',
      '#header' => [$this->t('Content Type'), $this->t('Operations')],
      '#rows' => $rows,
      'contexts' => ['user'],
      '#empty' => $this->t('No mappings found for any content type. Visit '
        . $content_config_url->setAbsolute()->toString() . ' page to create a mapping.'),
    ];
    $form['pager'] = [
      '#type' => 'pager'
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
