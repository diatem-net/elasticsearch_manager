<?php

namespace Drupal\elasticsearch_manager\ElasticSearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Drupal\Core\Render\Markup;

class ElasticSearchManager
{

  const LOGS_DISABLED = 0;

  /**
   * ElasticSearch host.
   *
   * @var string
   */
  private $host;

  /**
   * ElasticSearch index.
   *
   * @var string
   */
  private $index;

  /**
   * ElasticSearch logs level.
   *
   * @var integer
   */
  private $logs;

  /**
   * ElasticSearch client.
   *
   * @var \Elasticsearch\Client
   */
  private $client;

  /**
   * Debug mode
   *
   * @var boolean
   */
  private $debug;

  public function __construct()
  {
    $this->host  = \Drupal::config('elasticsearch_manager.settings')->get('host');
    $this->index = \Drupal::config('elasticsearch_manager.settings')->get('index');
    $this->logs  = \Drupal::config('elasticsearch_manager.settings')->get('logs');

    $builder = ClientBuilder::create();
    $builder->setHosts(array($this->host));
    if ($this->logs && $this->logs !== self::LOGS_DISABLED) {
      if ((is_file(self::getLogFile()) && is_writable(self::getLogFile()))
          || is_writable(self::getLogFolder())) {
        $logger = ClientBuilder::defaultLogger(self::getLogFile(), $this->logs);
        $builder->setLogger($logger);
      } else {
        throw new \Exception(sprintf('Elasticsearch log file is not writable (%s)', self::getLogFile()));
      }
    }
    $this->client = $builder->build();
  }

  /**
   * Get log folder
   *
   * @return string
   */
  public static function getLogFolder()
  {
    return realpath(DRUPAL_ROOT . '/../') . '/logs/';
  }

  /**
   * Get log file
   *
   * @return string
   */
  public static function getLogFile()
  {
    return self::getLogFolder() . 'elasticsearch.log';
  }

  /**
   * Close the index
   */
  public function closeIndex()
  {
    $params = array(
      'index' => $this->index
    );
    if ($this->client->indices()->exists($params)) {
      return $this->client->indices()->close($params);
    }
    return false;
  }

  /**
   * Open the index
   */
  public function openIndex()
  {
    $params = array(
      'index' => $this->index
    );
    if ($this->client->indices()->exists($params)) {
      return $this->client->indices()->open($params);
    }
    return false;
  }

  /**
   * Delete the index
   */
  public function deleteIndex()
  {
    $params = array(
      'index' => $this->index
    );
    if ($this->client->indices()->exists($params)) {
      return $this->client->indices()->delete($params);
    }
    return false;
  }

  /**
   * Create the index
   *
   * @param array $mappings
   *   Mappings description
   *
   * @param array $settings
   *   Settings description
   */
  public function createIndex($mappings = array(), $settings = array())
  {
    $params = array(
      'index' => $this->index
    );
    if (!$this->client->indices()->exists($params)) {
      $params['body'] = array(
        'mappings' => $mappings,
        'settings' => $settings
      );
      return $this->client->indices()->create($params);
    }
    return false;
  }

  /**
   * Index a document
   *
   * @param string  $type
   *   Document type
   *
   * @param integer $id
   *   Document id (can be null)
   *
   * @param array   $data
   *   Document data
   */
  public function indexDocument($type, $id, $data)
  {
    $params = array(
      'index' => $this->index,
      'type'  => $type,
      'body'  => $data
    );
    if ($id) {
      $params['id'] = $id;
    }
    return $this->client->index($params);
  }

  /**
   * Delete a document
   *
   * @param string  $type
   *   Document type
   *
   * @param integer $id
   *   Document id
   */
  public function deleteDocument($type, $id)
  {
    $params = array(
      'index' => $this->index,
      'type'  => $type,
      'id'    => $id
    );
    return $this->client->delete($params);
  }

  /**
   * Execute a search query
   *
   * @param array   $query
   *   Query description
   *
   * @param string  $type
   *   Document type
   */
  public function search($query, $type = null)
  {
    $params = array(
      'index' => $this->index,
      'body'  => $query
    );
    if ($type) {
      $params['type'] = $type;
    }

    return $this->client->search($params);
  }

}
