<?php

namespace Drupal\fivestar\Plugin\Field\FieldWidget;

use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'fivestar_stars' widget.
 *
 * @FieldWidget(
 *   id = "fivestar_stars",
 *   label = @Translation("Stars"),
 *   field_types = {
 *     "fivestar"
 *   }
 * )
 */
class StarsWidget extends FiveStartWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display_format' => 'average',
      'text_format' => 'none',
      'fivestar_widget' => drupal_get_path('module', 'fivestar') . '/widgets/basic/basic.css',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['fivestar_widget'] = [
      '#type' => 'radios',
      '#options' => $this->getAllWidget(),
      '#default_value' => $this->getSetting('fivestar_widget'),
      '#attributes' => ['class' => ['fivestar-widgets', 'clearfix']],
      '#pre_render' => [[$this, 'previewsExpand']],
      '#attached' => ['library' => ['fivestar/fivestar.admin']],
    ];
    return $elements;
  }

  /**
   *
   */
  public function previewsExpand(array $element) {
    foreach (Element::children($element) as $css) {
      $vars = [
        '#theme' => 'fivestar_preview_widget',
        '#css' => $css,
        '#name' => strtolower($element[$css]['#title']),
      ];
      $element[$css]['#description'] = \Drupal::service('renderer')
        ->render($vars);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widgets = $this->getAllWidget();
    $active = $this->getSetting('fivestar_widget');
    $display_settings = [
      'name' => isset($widgets[$active]) ? mb_strtolower($widgets[$active]) : 'default',
      'css' => $active,
    ] + $this->getSettings();
    $settings = $items[$delta]->getFieldDefinition()->getSettings();
    $display_settings += $settings;
    $is_field_config_form = ($form_state->getBuildInfo()['form_id'] == 'field_config_edit_form');
    $voting_is_allowed = (bool) ($settings['rated_while'] == 'editing') || $is_field_config_form;

    // To prevent error during rating settings save set positive rating value by default.
    $default_rating = $is_field_config_form ? 80 : 0;

    $element['rating'] = [
      '#type' => 'fivestar',
      '#stars' => $settings['stars'],
      '#allow_clear' => $settings['allow_clear'],
      '#allow_revote' => $settings['allow_revote'],
      '#allow_ownvote' => $settings['allow_ownvote'],
      '#default_value' => isset($items[$delta]->rating) ? $items[$delta]->rating : $default_rating,
      '#widget' => $display_settings,
      '#settings' => $display_settings,
      '#show_static_result' => !$voting_is_allowed,
    ];

    return $element;
  }

}
