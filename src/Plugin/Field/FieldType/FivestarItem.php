<?php

namespace Drupal\fivestar\Plugin\Field\FieldType;

use Drupal\Core\Link;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'fivestart' field type.
 *
 * @FieldType(
 *   id = "fivestar",
 *   label = @Translation("Fivestar Rating"),
 *   description = @Translation("Store a rating for this piece of content."),
 *   default_widget = "fivestar_stars",
 *   default_formatter = "fivestar_stars"
 * )
 */
class FivestarItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'rating' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
          'sortable' => TRUE,
        ],
        'target' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $property_definitions['rating'] = DataDefinition::create('integer')
      ->setLabel(t('Rating'));
    $property_definitions['target'] = DataDefinition::create('integer');
    return $property_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'stars' => 5,
      'allow_clear' => FALSE,
      'allow_revote' => TRUE,
      'allow_ownvote' => TRUE,
      'rated_while' => 'viewing',
      'enable_voting_target' => FALSE,
      'target_bridge_field' => '',
      'target_fivestar_field' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'vote_type' => 'vote',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];
    $vote_manager = \Drupal::service('fivestar.vote_manager');
    $vote_types_link = Link::createFromRoute($this->t('here'), 'entity.vote_type.collection')->toString();

    $element['vote_type'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Vote type'),
      '#options' => $vote_manager->getVoteTypes(),
      '#description' => $this->t(
        'The vote type this rating will affect. 
          Enter a property on which that this rating will affect, 
          such as <em>quality</em>, <em>satisfaction</em>, <em>overall</em>, etc.
          You can add new vote type %vote_types_link.', [
            '%vote_types_link' => $vote_types_link,
        ]
      ),
      '#default_value' => $this->getSetting('vote_type'),
      '#show_static_result' => $has_data,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['stars'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of stars'),
      '#options' => array_combine(range(1, 10), range(1, 10)),
      '#default_value' => $this->getSetting('stars'),
    ];
    $element['allow_clear'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to cancel their ratings.'),
      '#default_value' => $this->getSetting('allow_clear'),
      '#return_value' => 1,
    ];
    $element['allow_revote'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to re-vote on already voted content.'),
      '#default_value' => $this->getSetting('allow_revote'),
      '#return_value' => 1,
    ];
    $element['allow_ownvote'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to vote on their own content.'),
      '#default_value' => $this->getSetting('allow_ownvote'),
      '#return_value' => 1,
    ];
    $element['rated_while'] = [
      '#type' => 'radios',
      '#default_value' => $this->getSetting('rated_while'),
      '#title' => $this->t('Select when user can rate the field'),
      '#options' => [
        'viewing' => 'Rated while viewing',
        'editing' => 'Rated while editing',
      ],
    ];
    $element['enable_voting_target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set voting target'),
      '#default_value' => $this->getSetting('enable_voting_target'),
    ];
    $states = [
      'visible' => [
        ':input[name="settings[enable_voting_target]"]' => ['checked' => TRUE],
      ],
      'required' => [
        ':input[name="settings[enable_voting_target]"]' => ['checked' => TRUE],
      ],
    ];
    $element['target_bridge_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Target bridge field'),
      '#description' => $this->t(
        'Machine name of field that binds current entity with entity that contain target fivestar field. 
        The field should have "entity_reference" type.'
      ),
      '#states' => $states,
      '#default_value' => $this->getSetting('target_bridge_field'),
    ];
    $element['target_fivestar_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Target fivestar field'),
      '#description' => $this->t('Machine name of fivestar field which should affect after vote.'),
      '#states' => $states,
      '#default_value' => $this->getSetting('target_fivestar_field'),
    ];
    $element['#element_validate'] = [
      [get_class($this), 'fieldSettingsFormValidate'],
    ];

    // @todo try to find the way to omit it.
    $form_state->set('host_entity', $this->getEntity());

    return $element;
  }

  /**
   * Validate callback: check field settings.
   */
  public static function fieldSettingsFormValidate(array $form, FormStateInterface $form_state) {
    $host_entity = $form_state->get('host_entity');
    $field_settings = $form_state->getValue('settings');

    // Validate voting target settings.
    if ($field_settings['enable_voting_target'] == 1) {
      // Check if bridge field exist.
      if (!$host_entity->hasField($field_settings['target_bridge_field'])) {
        $form_state->setErrorByName(
          'target_bridge_field',
          t('The host entity doesn\'t contain field: "@field_name"', [
            '@field_name' => $field_settings['target_bridge_field'],
          ])
        );
        return;
      }

      // Check if bridge field has correct type.
      $field_type = $host_entity->get($field_settings['target_bridge_field'])->getFieldDefinition()->getType();
      if ($field_type != 'entity_reference') {
        $form_state->setErrorByName(
          'target_bridge_field',
          t('The bridge field must have "entity_reference" type. The entered field has type: "@field_type"', [
            '@field_type' => $field_type,
          ])
        );
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $rating = $this->get('rating')->getValue();
    return empty($rating) || $rating == '-';
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $vote_rating = 0;
    $entity = $this->getEntity();
    $field_definition = $this->getFieldDefinition();
    $field_name = $field_definition->getName();
    $field_settings = $field_definition->getSettings();
    $vote_manager = \Drupal::service('fivestar.vote_manager');
    $target_entity = $this->getTargetEntity($entity, $field_settings);

    if ($entity->isPublished()) {
      $vote_rating = $entity->get($field_name)->rating ?: 0;
    }

    // Delete previous user vote.
    $current_user = \Drupal::currentUser();
    $criteria = [
      'entity_id' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId(),
      'type' => $field_settings['vote_type'],
      'user_id' => $current_user->id(),
    ];
    if ($current_user->isAnonymous()) {
      $criteria['vote_source'] = hash('sha256', serialize(\Drupal::request()->getClientIp()));
    }
    foreach ($vote_manager->getVotesByCriteria($criteria) as $vote) {
      $vote->delete();
    }

    $vote_manager->addVote($entity, $vote_rating, $field_settings['vote_type']);
    if (!empty($target_entity)) {
      $vote_manager->addVote($target_entity, $vote_rating, $field_settings['vote_type']);
    }
  }

  /**
   * Get target entity.
   *
   * @param FieldableEntityInterface $entity
   * @param array $field_settings
   * @return FieldableEntityInterface|NULL
   */
  public function getTargetEntity(FieldableEntityInterface $entity, array $field_settings) {
    if ($field_settings['enable_voting_target'] !== 1) {
      return NULL;
    }
    if (!$entity->hasField($field_settings['target_bridge_field'])) {
      return NULL;
    }

    $bridge_entity = $entity->{$field_settings['target_bridge_field']}->entity;
    if ($bridge_entity && $bridge_entity->hasField($field_settings['target_fivestar_field'])) {
      return $bridge_entity;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $del_entity = $this->getEntity();
    $field_settings = $this->getFieldDefinition()->getSettings();
    $target_entity = $this->getTargetEntity($del_entity, $field_settings);

    if (!$target_entity) {
      return;
    }

    $vote_storage = \Drupal::entityTypeManager()->getStorage('vote');
    $votes = $vote_storage->loadByProperties([
      'entity_type' => $del_entity->getEntityTypeId(),
      'entity_id' => $del_entity->id(),
    ]);

    foreach ($votes as $vote) {
      // Get target vote.
      $target_votes = $vote_storage->loadByProperties([
        'entity_type' => $target_entity->getEntityTypeId(),
        'entity_id' => $target_entity->id(),
        'type' => $vote->bundle(),
        'user_id' => $vote->getOwnerId(),
        'value' => $vote->getValue(),
        'vote_source' => $vote->getSource(),
      ]);

      foreach ($target_votes as $target_vote) {
        $target_vote->delete();
      }
    }
  }

}
