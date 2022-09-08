<?php
/**
 * @file
 * Contains \Drupal\stage2_admin\Plugin\QueueWorker\CacheTileQueue.
 */
namespace Drupal\stage2_admin\Plugin\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * @QueueWorker(
 *   id = "tile_queue"
 * )
 */

class CacheTileQueue extends QueueWorkerBase {
  public function processItem($data) {
    \Drupal::logger('stage2_admin')->notice('process item: '.$data['id']);
  }
}