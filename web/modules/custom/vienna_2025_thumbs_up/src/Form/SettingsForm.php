<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_thumbs_up\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure vienna_2025_thumbs_up settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'vienna_2025_thumbs_up_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['vienna_2025_thumbs_up.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['config'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Config'),
      '#default_value' => $this->config('vienna_2025_thumbs_up.settings')->get('config'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $config = $form_state->getValue('config') ;
    try {
      json_decode($config, false, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $exception) {
      $form_state->setErrorByName(
        'message',
        $exception->getMessage(),
      );
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('vienna_2025_thumbs_up.settings')
      ->set('config', $form_state->getValue('config'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
