<?php

namespace Drupal\ham_station\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form for the ham map page.
 */
class HamMapForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ham_map_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#theme'] = 'form_content';

    $form['query_type'] = [
      '#type' => 'radios',
      '#options' => [
        'c' => 'Callsign',
        'g' => 'Gridsquare',
        'z' => 'Zip code',
        'a' => 'Street address',
      ],
      '#default_value' => 'c',
    ];

    $form['query'] = [
      '#type' => 'textfield',
      '#title' => t('Callsign'),
      '#description' => t('Enter a callsign.'),
      '#wrapper_attributes' => ['class' => ['query-other']],
    ];

    $form['address'] = [
      '#type' => 'textfield',
      '#title' => t('Street address'),
      '#description' => t('Enter / select a street address.'),
      '#wrapper_attributes' => ['class' => ['query-address hidden']],
    ];

    $form['error'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['error-message', 'hidden']],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show the map'),
      '#attributes' => ['class' => ['btn', 'btn-primary']],
    ];

    $form['show_gridlabels'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show grid squares'),
      '#default_value' => TRUE,
    ];

    $form['processing'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Processing...'),
      '#attributes' => ['class' => ['processing']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Not used but required by the interface.
  }

}
