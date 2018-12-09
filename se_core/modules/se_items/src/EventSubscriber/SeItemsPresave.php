<?php

namespace Drupal\se_items\EventSubscriber;

use Drupal\Core\Entity\EntityTypeEvent;
use Drupal\se_core\Event\SeCoreEvent;
use Drupal\se_core\Event\SeCoreEvents;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityTypeManager;

class SeItemsPresave implements EventSubscriberInterface {
  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * SeItemPresave constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SeCoreEvents::NODE_PRESAVE => 'preSave',
    ];
  }

  /**
   * When an item is saved, create an associated stock item.
   *
   * @param \Drupal\Core\Entity\EntityTypeEvent $event
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function preSave(SeCoreEvent $event) {
    $node = $event->getNode();
    $total = 0;

    $bundles = [
      'se_bill'           => 'bi',
      'se_goods_receipt'  => 'gr',
      'se_invoice'        => 'in',
      'se_quote'          => 'qu',
      'se_purchase_order' => 'po',
    ];

    if (!in_array($node->bundle(), array_keys($bundles))) {
      return;
    }

    /** @var \Drupal\node\Entity\Node $node */
    $items = $node->{'field_' . $bundles[$node->bundle()] . '_items'}->referencedEntities();

    foreach ($items as $ref_entity) {
      $total += $ref_entity->field_it_quantity->value * $ref_entity->field_it_price->value;
    }

    /** @var \Drupal\node\Entity\Node $entity */
    $node->{'field_' . $bundles[$node->bundle()] . '_total'}->setValue($total);
  }

}