<?php

namespace Drupal\ham_station\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\ham_station\AjaxCommands\MapQueryCommand;
use Drupal\ham_station\Form\HamMapForm;
use Drupal\ham_station\Query\MapQueryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  /**
   * @var MapQueryService
   */
  private $mapQueryService;

  /**
   * @var Serializer
   */
  private $serializer;

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  private $blockManager;

  public function __construct(
    MapQueryService $map_query_service,
    Serializer $serializer,
    BlockManagerInterface $block_manager,
  ) {
    $this->mapQueryService = $map_query_service;
    $this->serializer = $serializer;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ham_station.map_query_service'),
      $container->get('serializer'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * Ham map page.
   *
   * @param string $query_type|null
   *   Initial query type.
   * @param string $query_value|null
   *   Initial query value.
   *
   * @return array
   */
  public function hamMap($query_type, $query_value) {

    if (!empty($query_type) && empty($query_value)) {
      // Allow url like /KT1F.
      $query_value = $query_type;
      $query_type = 'c';
    }

    $block_content_storage = $this->entityTypeManager()->getStorage('block_content');

    $block_ids = $block_content_storage->getQuery()->accessCheck()
      ->condition('info', 'neighbors-info-', 'STARTS_WITH')
      ->execute();

    $blocks = $block_content_storage->loadMultiple($block_ids);
    $info_blocks = [];

    foreach($blocks as $block) {
      /** @var \Drupal\block_content\BlockContentInterface $block */
      $plugin_id = "block_content:{$block->uuid()}";
      $instance = $this->blockManager->createInstance($plugin_id);
      $parts = explode('-', $block->get('info')->value);
      $info_blocks[end($parts)] = $instance->build();
    }

    $export_link = NULL;
    if ($this->currentUser()->hasPermission('export ham station')) {
      $export_link = Link::createFromRoute($this->t('Export to file'), 'ham_station.map_export');
    }

    return [
      '#theme' => 'ham_neighbors',
      '#form' => $this->formBuilder()->getForm(HamMapForm::class),
      '#info_blocks' => $info_blocks,
      '#export_link' => $export_link,
      '#attached' => [
        'library' => ['ham_station/neighbors'],
        'drupalSettings' => [
          'ham_station' => ['query_type' => $query_type, 'query_value' => $query_value],
        ]
      ],
    ];
  }

  public function hamMapAjax(Request $request) {
    $query_type = $request->query->get('queryType');
    $query_value = $request->query->get('value');

    $result = $this->mapQueryService->mapQuery($query_type, $query_value);

    $response = new AjaxResponse();
    $cmd = new MapQueryCommand($result);
    $response->addCommand($cmd);

    // Use Symfony serializer.
    // By default the command uses json_encode which doesn't handle objects well.
    $response->setJson(
      $this->serializer->serialize([$cmd->render()], 'json')
    );

    return $response;
  }

  /**
   * Invalidate cache tag for geocode report.
   *
   * @return Response
   */
  public function invalidateGeocodeCache() {
    Cache::invalidateTags(['geocoding']);
    return new Response('', 204);
  }

}
