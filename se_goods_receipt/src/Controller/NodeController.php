<?php

declare(strict_types=1);

namespace Drupal\se_goods_receipt\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeTypeInterface;
use Drupal\se_core\ErpCore;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Node routes.
 */
class NodeController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, EntityRepositoryInterface $entity_repository = NULL) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    if (!$entity_repository) {
      @trigger_error('The entity.repository service must be passed to NodeController::__construct(), it is required before Drupal 9.0.0. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $entity_repository = \Drupal::service('entity.repository');
    }
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('entity.repository')
    );
  }

  /**
   * Provides the node submission form.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type entity for the node.
   *
   * @param \Drupal\node\Entity\Node $source
   *
   * @return array
   *   A node submission form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function add(NodeTypeInterface $node_type, Node $source) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->entityTypeManager()->getStorage('node')->create([
      'type' => $node_type->id(),
    ]);

    foreach ($source->{'se_' . ErpCore::ITEM_LINE_NODE_BUNDLE_MAP[$source->bundle()] . '_lines'} as $index => $item_line) {
      // TODO - Ensure we're using the non-serialised item here?
      $item_count = $item_line->quantity;
      for ($i = 0; $i < $item_count; $i++) {
        $node->{'se_' . ErpCore::ITEM_LINE_NODE_BUNDLE_MAP[$node->bundle()] . '_lines'}->appendItem($item_line);
      }
    }

    $node->se_bu_ref->target_id = $source->se_bu_ref->target_id;
    $node->se_co_ref->target_id = $source->se_co_ref->target_id;
    $node->{'se_' . ErpCore::ITEM_LINE_NODE_BUNDLE_MAP[$node->bundle()] . '_purchase_order_ref'}->target_id = $source->id();

    return $this->entityFormBuilder()->getForm($node);
  }

}
