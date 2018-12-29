<?php

/**
 * @file
 * Provides API documentation for the fivestar module.
 */

/**
 * Implementation of hook_fivestar_widgets().
 *
 * This hook allows other modules to create additional custom widgets for
 * the fivestar module.
 *
 * @return array
 *   An array of key => value pairs suitable for inclusion as the #options in a
 *   select or radios form element. Each key must be the location of a css
 *   file for a fivestar widget. Each value should be the name of the widget.
 *
 * @see fivestar_fivestar_widgets()
 */
function hook_fivestar_widgets() {
  // Letting fivestar know about my Cool and Awesome Stars.
  $widgets = [
    'path/to/my/awesome/fivestar/css.css' => 'Awesome Stars',
    'path/to/my/cool/fivestar/css.css' => 'Cool Stars',
  ];

  return $widgets;
}

/**
 * Implementation of hook_fivestar_access().
 *
 * This hook is called before every vote is cast through Fivestar. It allows
 * modules to allow or deny voting on any type of entity, such as nodes, users, or
 * comments.
 *
 * @param $entity_type
 *   Type entity.
 * @param $id
 *   Identifier within the type.
 * @param $tag
 *   The VotingAPI tag string.
 * @param $uid
 *   The user ID trying to cast the vote.
 *
 * @return boolean or NULL
 *   Returns TRUE if voting is supported on this object.
 *   Returns NULL if voting is not supported on this object by this module.
 *   If needing to absolutely deny all voting on this object, regardless
 *   of permissions defined in other modules, return FALSE. Note if all
 *   modules return NULL, stating no preference, then access will be denied.
 *
 * @see fivestar_validate_target()
 * @see fivestar_fivestar_access()
 */
function hook_fivestar_access($entity_type, $id, $vote_type, $uid) {
  if ($uid == 1) {
    // We are never going to allow the admin user case a fivestar vote.
    return FALSE;
  }
}
