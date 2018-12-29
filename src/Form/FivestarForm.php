<?php

namespace Drupal\fivestar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

/**
 * Fivestar form.
 */
class FivestarForm extends FormBase {

  /**
   * Form counter.
   *
   * @var int
   */
  private static $form_counter = 0;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    self::$form_counter += 1;

    // For correct submit work set unique name for every form in page.
    return 'fivestar_form_' . self::$form_counter;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $context = []) {
    $entity = $context['entity'];
    $uniq_id = Html::getUniqueId('vote');
    $field_definition = $context['field_definition'];
    $field_settings = $field_definition->getSettings();
    $field_name = $field_definition->getName();
    $result_manager = \Drupal::service('fivestar.vote_result_manager');
    $voting_is_allowed = (bool) ($field_settings['rated_while'] == 'viewing');

    $form['vote'] = [
      '#type' => 'fivestar',
      '#stars' => $field_settings['stars'],
      '#allow_clear' => $field_settings['allow_clear'],
      '#allow_revote' => $field_settings['allow_revote'],
      '#allow_ownvote' => $field_settings['allow_ownvote'],
      '#widget' => $context['display_settings'],
      '#default_value' => $entity->get($field_name)->rating,
      '#values' => $result_manager->getResultsByVoteType($entity, $field_settings['vote_type']),
      '#settings' => $context['display_settings'],
      '#show_static_result' => !$voting_is_allowed,
      '#attributes' => [
        'class' => ['vote'],
      ],
    ];

    // Click on this element triggered from JS side.
    $form['submit'] = [
      '#type' => 'submit',
      '#ajax' => [
        'event' => 'click',
        'callback' => '::fivestarAjaxVote',
        'method' => 'replace',
        'wrapper' => $uniq_id,
        'effect' => 'fade',
      ],
      '#attributes' => [
        'style' => 'display:none',
      ],
    ];

    $form_state->set('context', $context);
    $form_state->set('uniq_id', $uniq_id);
    $form['#attributes']['id'] = $uniq_id;

    return $form;
  }

  /**
   * Ajax callback: update fivestar form after voting.
   */
  public function fivestarAjaxVote(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $context = $form_state->get('context');
    
    if (isset($context['entity'])) {
      $entity = $context['entity'];
      $fivestar_field_name = $context['field_definition']->getName();
      if ($entity->hasField($fivestar_field_name)) {
        // For votingapi value will be save during save rating value to field storage.
        $entity->set($fivestar_field_name, $form_state->getValue('vote'));
        $entity->save();
      }
    }

    $form_state->setRebuild(TRUE);
  }
}
