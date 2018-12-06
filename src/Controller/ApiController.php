<?php

namespace Drupal\elasticsearch_manager\Controller;

use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\elasticsearch_manager\Form\ConfigForm;
use Drupal\elasticsearch_manager\ElasticSearch\Indexer\NodeIndexer;
use Drupal\elasticsearch_manager\ElasticSearch\ElasticSearchManager;

class ApiController
{

  /**
   * Add or update several nodes to the index.
   * The amount of nodes is defined by config.
   *
   * @param string $type
   *   The type of node to index.
   *
   * @param integer $from
   *   Shift, for bulk indexation.
   *
   * @return JsonResponse
   */
  public function batch($type, $from = 0)
  {
    $data = array();

    // Check if this type is active
    $active = \Drupal::config('elasticsearch_manager.types')->get($type);
    if (!$active) {
      $data = array(
        'error' => t('This type is not enabled in ElasticSearch Manager configuration.', array(), array('context' => 'elasticsearch_manager'))
      );
    } else {

      $batchSize = \Drupal::config('elasticsearch_manager.settings')->get('batch_size');
      if (!$batchSize) {
        $batchSize = ConfigForm::DEFAULT_BATCH_SIZE;
      }

      // Search for all nodes from type
      $query = \Drupal::entityQuery('node')
        ->condition('type', $type)
        ->condition('status', NODE_PUBLISHED)
        ->range($from, $batchSize);
      $nids = $query->execute();

      $data = array(
        'success' => 0,
        'failure' => 0
      );

      $em = new ElasticSearchManager();
      $indexer = new NodeIndexer($em);

      foreach ($nids as $nid) {
        try {
          $node = Node::load($nid);

          $result = $indexer->insert($node);
          if ($result == NodeIndexer::OPERATION_SUCCESS) {
            $data['success']++;
          } else {
            $data['failure']++;
          }
        } catch(\Exception $e) {
          $data['failure']++;
        }
      }
    }

    return new JsonResponse($data);
  }

  /**
   * Add or update a node to the index
   *
   * @param integer $nid
   *   The id of the node that needs to be deleted.
   *
   * @return JsonResponse
   */
  public function index($nid)
  {
    $data = array();

    $em = new ElasticSearchManager();
    $indexer = new NodeIndexer($em);

    try {
      $node = Node::load($nid);

      $result = $indexer->insert($node);
      if ($result == NodeIndexer::OPERATION_SUCCESS) {
        $data = array(
          'success' => sprintf(t('The node #%u has been indexed.', array(), array('context' => 'elasticsearch_manager')), $nid)
        );
      } else {
        $data = array(
          'error' => sprintf(t('An error occured during the indexation of node #%u.', array(), array('context' => 'elasticsearch_manager')), $nid)
        );
      }
    } catch(\Exception $e) {
      $data = array(
        'error' => sprintf(t('Elasticsearch connection problem, the node #%u has not been indexed.', array(), array('context' => 'elasticsearch_manager')), $nid)
      );
    }

    return new JsonResponse($data);
  }

  /**
   * Delete a node from the index.
   *
   * @param integer $nid
   *   The id of the node that needs to be deleted.
   *
   * @return JsonResponse
   */
  public function delete($nid)
  {
    $data = array();

    $em = new ElasticSearchManager();
    $indexer = new NodeIndexer($em);

    try {
      $node = Node::load($nid);

      $result = $indexer->delete($node);
      if ($result == NodeIndexer::OPERATION_SUCCESS) {
        $data = array(
          'success' => sprintf(t('The node #%u has been remove from the index.', array(), array('context' => 'elasticsearch_manager')), $nid)
        );
      } else {
        $data = array(
          'error' => sprintf(t('An error occured during the deletion of node #%u from the index.', array(), array('context' => 'elasticsearch_manager')), $nid)
        );
      }
    } catch(\Exception $e) {
      $data = array(
        'error' => sprintf(t('Elasticsearch connection problem, the node #%u has not been removed.', array(), array('context' => 'elasticsearch_manager')), $nid)
      );
    }

    return new JsonResponse($data);
  }
  
	/**
   * Check if index is alive.
   *
   * @return JsonResponse
   */
  public function checkIndex()
  {
    $em = new ElasticSearchManager();
    return JsonResponse($em->checkIndex());
  }

}
