<?php

namespace Drupal\fivestar\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'fivestar_stars' formatter.
 *
 * @FieldFormatter(
 *   id = "fivestar_stars",
 *   label = @Translation("As stars"),
 *   field_types = {
 *     "fivestar"
 *   },
 *   weight = 1
 * )
 */
class StarsFormatter extends FiveStarFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'text_format' => 'average',
      'display_format' => 'average',
      'fivestar_widget' => drupal_get_path('module', 'fivestar') . '/widgets/basic/basic.css',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $entity = $items->getEntity();
    $widgets = $this->getAllWidget();
    $form_builder = \Drupal::formBuilder();
    $widget_css_path = $this->getSetting('fivestar_widget');
    $display_settings = [
      'name' => mb_strtolower($widgets[$widget_css_path]),
      'css' => $widget_css_path,
    ] + $this->getSettings();

    if (!$items->isEmpty()) {
      /** @var \Drupal\fivestar\Plugin\Field\FieldType\FivestarItem $item */
      foreach ($items as $delta => $item) {
        $context = [
          'entity' => $entity,
          'field_definition' => $item->getFieldDefinition(),
          'display_settings' => $display_settings,
        ];

        $elements[$delta] = $form_builder->getForm(
          '\Drupal\fivestar\Form\FivestarForm', $context
        );
      }
    }
    // Load empty form ('No votes yet') if there are no items.
    else {
      $bundle_fields = \Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions($entity->getEntityType()->id(), $entity->bundle());
      $field_definition = $bundle_fields[$items->getName()];

      $context = [
        'entity' => $entity,
        'field_definition' => $field_definition,
        'display_settings' => $display_settings,
      ];

      $elements[] = $form_builder->getForm(
        '\Drupal\fivestar\Form\FivestarForm', $context
      );
    }

    return $elements;
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
      '#attributes' => [
        'class' => [
          'fivestar-widgets',
          'clearfix',
        ]
      ],
      '#pre_render' => [
        [$this, 'previewsExpand'],
      ],
      '#attached' => [
        'library' => ['fivestar/fivestar.admin'],
      ],
    ];

    $elements['display_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Value to display as stars'),
      '#options' => [
        'average' => $this->t('Average vote'),
      ],
      '#default_value' => $this->getSetting('display_format'),
    ];

    $elements['text_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Text to display under the stars'),
      '#options' => [
        'none' => $this->t('No text'),
        'average' => $this->t('Average vote'),
      ],
      '#default_value' => $this->getSetting('text_format'),
    ];

    return $elements;
  }

}
