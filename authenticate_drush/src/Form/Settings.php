<?php

namespace Drupal\authenticate_drush\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Drush Authenticate module Settings.
 *
 * @package Drupal\drush_authenticate\Form
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return [
      'authenticate_drush_config.settings',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'authenticate_drush_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $description = $this->t('One command name per line.<br />
    Examples: <ul>
    <li>config:set</li>
    <li>config:delete</li>
    <li>theme:enable</li>
    <li>theme:uninstall</li>
    </ul>');

    $settings = $this->config('authenticate_drush_config.settings');
    $commands = $settings->get('drush_commands');

    $form['drush_commands'] = [
      '#type' => 'textarea',
      '#rows' => 10,
      '#title' => $this->t('Critical drush commands'),
      '#description' => $description,
      '#default_value' => !empty($commands) ? implode(PHP_EOL, $commands) : '',
      '#size' => 60,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $drush_commands = preg_split("/[\r\n]+/", $values['drush_commands']);
    $this->config('authenticate_drush_config.settings')
      ->set('drush_commands', $drush_commands)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
