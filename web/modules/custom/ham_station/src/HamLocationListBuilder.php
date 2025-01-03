<?php

namespace Drupal\ham_station;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Ham location entities.
 *
 * @ingroup ham_station
 */
class HamLocationListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Ham location ID');
    $header['coords'] = $this->t('Coordinates');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\ham_station\Entity\HamLocation */
    $row['id'] = Link::createFromRoute(
      $entity->id(),
      'entity.ham_location.edit_form',
      ['ham_location' => $entity->id()]
    );
    $row['coords'] = "{$entity->get('latitude')->value} | {$entity->get('longitude')->value}";
    return $row + parent::buildRow($entity);
  }

}
