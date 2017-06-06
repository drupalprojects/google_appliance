<?php

namespace Drupal\google_appliance\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SearchFrom.
 *
 * @package Drupal\google_appliance\Form
 */
class SearchFrom extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_appliance_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request();

    $query = $request->request->has('search_keys') ? $request->request->get('search_keys') : '';

    $prompt = $this->t('Enter the terms you wish to search for.');

    // Basic search.
    $form['basic'] = [
      '#type' => 'container',
    ];
    $form['basic']['search_keys'] = [
      '#type' => 'textfield',
      '#default_value' => $query,
      '#attributes' => [
        'title' => $prompt,
        'placeholder' => $prompt,
      ],
      '#title' => $prompt,
      '#title_display' => 'invisible',
    ];

    // @todo: sort.

    $form['basic']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Search'),
    ];

    // Use core search CSS in addition to this module's css
    // (keep it general in case core search is enabled).
    $form['#attributes']['class'][] = 'search-form';
    $form['#attributes']['class'][] = 'search-google-appliance-search-form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $searchQuery = urlencode($form_state->getValue('search_keys'));

    $form_state->setRedirect('google_appliance.search_view', [
      'search_query' => $searchQuery,
    ]);
  }

}
