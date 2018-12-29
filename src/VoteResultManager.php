<?php

namespace Drupal\fivestar;

use Drupal\votingapi\VoteResultFunctionManager;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Contain methods for manage votes results.
 *
 * @package Drupal\fivestar
 */
class VoteResultManager {

  /**
   * The vote result manager.
   *
   * @var \Drupal\votingapi\VoteResultFunctionManager
   */
  protected $voteResultManager;

  /**
   * Constructs a new VoteResultManager object.
   *
   * @param VoteResultFunctionManager $vote_result_manager
   */
  public function __construct(VoteResultFunctionManager $vote_result_manager) {
    $this->voteResultManager = $vote_result_manager;
  }

  /**
   * Get votes for passed entity based on vote type.
   *
   * @param FieldableEntityInterface $entity
   * @param string $vote_type
   * @return array
   */
  public function getResultsByVoteType(FieldableEntityInterface $entity, $vote_type) {
    $results = $this->getResults($entity);
    if (isset($results[$vote_type])) {
      return $results[$vote_type];
    }

    return $this->getDefaultResults();
  }

  /**
   * Get all votes results for passed entity.
   *
   * @param FieldableEntityInterface $entity
   * @return array
   */
  public function getResults(FieldableEntityInterface $entity) {
    $results = $this->voteResultManager->getResults(
      $entity->getEntityTypeId(),
      $entity->id()
    );

    return !empty($results) ? $results : $this->getDefaultResults();
  }

  /**
   * Return default result collection.
   *
   * @return array
   */
  public function getDefaultResults() {
    return [
      'vote_sum' => 0,
      'vote_user' => 0,
      'vote_count' => 0,
      'vote_average' => 0,
    ];
  }

  /**
   * Recalculate votes results.
   *
   * @param FieldableEntityInterface $entity
   */
  public function recalculateResults(FieldableEntityInterface $entity) {
    $this->voteResultManager->recalculateResults(
      $entity->getEntityTypeId(),
      $entity->id(),
      $entity->bundle()
    );
  }

}
