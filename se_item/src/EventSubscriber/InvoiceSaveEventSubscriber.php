<?php

declare(strict_types=1);

namespace Drupal\se_item\EventSubscriber;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\hook_event_dispatcher\Event\Entity\EntityInsertEvent;
use Drupal\hook_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Drupal\hook_event_dispatcher\Event\Entity\EntityUpdateEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\se_core\ErpCore;
use Drupal\se_item\Entity\Item;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class InvoiceSaveEventSubscriber.
 *
 * Update item status if they have been invoiced out.
 *
 * @package Drupal\se_item_line\EventSubscriber
 */
class InvoiceSaveEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    /** @noinspection PhpDuplicateArrayKeysInspection */
    return [
      HookEventDispatcherInterface::ENTITY_INSERT => 'invoiceInsertMarkSold',
      HookEventDispatcherInterface::ENTITY_UPDATE => 'invoiceUpdateMarkSold',
      HookEventDispatcherInterface::ENTITY_PRE_SAVE => 'invoiceMarkAvailable',
    ];
  }

  /**
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\EntityInsertEvent $event
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function invoiceInsertMarkSold(EntityInsertEvent $event): void {
    /** @var \Drupal\node\Entity\Node $entity */
    $entity = $event->getEntity();

    if ($entity->getEntityTypeId() === 'node' && $entity->bundle() === 'se_invoice') {
      $this->nodeMarkItemStatus($entity);
    }
  }

  /**
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\EntityUpdateEvent $event
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function invoiceUpdateMarkSold(EntityUpdateEvent $event): void {
    /** @var \Drupal\node\Entity\Node $entity */
    $entity = $event->getEntity();

    if ($entity->getEntityTypeId() === 'node' && $entity->bundle() === 'se_invoice') {
      $this->nodeMarkItemStatus($entity);
    }
  }

  /**
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\EntityPresaveEvent $event
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function invoiceMarkAvailable(EntityPresaveEvent $event): void {
    /** @var \Drupal\node\Entity\Node $entity */
    $entity = $event->getEntity();

    if ($entity->getEntityTypeId() === 'node' && $entity->bundle() === 'se_invoice') {
      $this->nodeMarkItemStatus($entity, FALSE);
    }
  }

  /**
   * Loop through the payment entries and mark the invoices as
   * paid/unpaid as dictated by the parameter.
   *
   * @param \Drupal\node\Entity\Node $entity
   * @param bool $sold
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function nodeMarkItemStatus($entity, $sold = TRUE): void {
    $bundle_field_type = 'se_' . ErpCore::ITEM_LINE_NODE_BUNDLE_MAP[$entity->bundle()];
    $date = new DateTimePlus(NULL, date_default_timezone_get());

    foreach ($entity->{$bundle_field_type . '_lines'} as $index => $item_line) {
      if ($item_line->target_type === 'se_item') {
        /** @var \Drupal\se_item\Entity\Item $item */
        if (($item = Item::load($item_line->target_id)) && in_array($item->bundle(), ['se_stock', 'se_assembly'])) {
          // TODO - Make a service for this?
          // Only operate on things that have a 'parent', not the parents
          // themselves, they are never sold.
          if (!empty($item->se_it_item_ref->target_id)) {
            if (isset($item->se_it_invoice_ref->value) !== $sold) {
              if ($sold) {
                $item->se_it_sale_date->value = $date->format('Y-m-d');
                $item->se_it_sale_price->value = $item_line->price;
                $item->se_it_invoice_ref->target_id = $entity->id();
              }
              else {
                unset($item->se_it_sale_date->value, $item->se_it_sale_price->value, $item->se_it_invoice_ref->target_id);
              }
              $item->save();
            }
          }
        }
      }
    }
  }

}
