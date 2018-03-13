<?php

namespace Drupal\elasticsearch_manager\ElasticSearch\Indexer;

use Drupal\node\Entity\NodeType;
use Drupal\file\Entity\File;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\MappingFieldFactory;
use Drupal\elasticsearch_manager\ElasticSearch\ElasticSearchManager;

class NodeIndexer
{

  const OPERATION_CANCELLED = -1;
  const OPERATION_ERROR = 0;
  const OPERATION_NOT_APPLICABLE = 1;
  const OPERATION_SUCCESS = 2;

  const UNIQUE_TYPE = 'elements';

  /**
    * ElasticSearch manager.
    *
    * @var \Drupal\elasticsearch_manager\ElasticSearch\ElasticSearchManager
    */
  protected $em;

  public function __construct(ElasticSearchManager $em) {
      $this->em = $em;
  }

  /**
    * Return default settings
    */
  public static function getDefaultSettings()
  {
    return array(
      'index' => array(
        'number_of_shards' => '1',
        'number_of_replicas' => '0',
        'analysis' => array(
          'analyzer' => array(
            'facet_analyzer' => array(
              'type' => 'custom',
              'tokenizer' => 'keyword'
            ),
            'french_analyzer' => array(
              'filter' => array(
                'french_elision',
                'lowercase',
                'asciifolding',
                'french_stop',
                'french_stemmer'
              ),
              'tokenizer' => 'standard'
            ),
            'default' => array(
              'filter' => array(
                'lowercase',
                'asciifolding'
              ),
              'tokenizer' => 'standard'
            )
          ),
          'filter' => array(
            'french_stemmer' => array(
              'type' => 'stemmer',
              'language' => 'light_french'
            ),
            'french_elision' => array(
              'type' => 'elision',
              'articles_case' => 'true',
              'articles' => array(
                'l', 'm', 't', 'qu', 'n', 's', 'j', 'd', 'c', 'jusqu', 'quoiqu', 'lorsqu', 'puisqu'
              )
            ),
            'nGram_filter' => array(
              'max_gram' => '20',
              'min_gram' => '2',
              'type' => 'nGram',
              'token_chars' => array(
                'letter',
                'digit',
                'punctuation',
                'symbol'
              )
            ),
            'french_stop' => array(
              'type' => 'stop',
              'stopwords' => '_none_'
            )
          )
        )
      )
    );
  }

  /**
  * Search if node is indexed
  *
  * @param object $node
  *   The node to search.
  *
  * @return boolean
  *   Has the node been found?
  */
  public function search($node)
  {
    $params = array(
      'query' => array(
        'match' => array(
          '_id' => $node->id()
        )
      )
    );

    return $this->em->search($params, self::UNIQUE_TYPE);
  }

  /**
  * Insert a new node in the index.
  *
  * @param object $node
  *   The node that needs to be inserted.
  *
  * @return boolean
  *   Operation's success.
  */
  public function insert($node)
  {
    $params = $this->prepareNode($node);
    if (!$params) {
      return self::OPERATION_NOT_APPLICABLE;
    }

    $result = $this->em->indexDocument(self::UNIQUE_TYPE, $node->id(), $params);
    if ($result && $result['result'] !== 'created') {
      return self::OPERATION_ERROR;
    }

    return self::OPERATION_SUCCESS;
  }

  /**
  * Update a new node in the index.
  *
  * @param object $node
  *   The node that needs to be updated.
  *
  * @param boolean $orInsert
  *   Should the node be inserted if not found?
  *
  * @return boolean
  *   Operation's success.
  */
  public function update($node, $orInsert = false)
  {
    $params = $this->prepareNode($node);
    if (!$params) {
      return self::OPERATION_NOT_APPLICABLE;
    }

    $result = $this->search($node);
    if ($result && $result['hits']['total'] !== 1) {
      if ($orInsert) {
        return $this->insert($node);
      }
      return self::OPERATION_ERROR;
    }

    $index = $this->em->indexDocument(self::UNIQUE_TYPE, $result['hits']['hits'][0]['_id'], $params);

    return self::OPERATION_SUCCESS;
  }

  /**
    * Delete a new node from the index.
    *
    * @param object $node
    *   The node that needs to be deleted.
    *
    * @return boolean
    *   Operation's success.
    */
  public function delete($node)
  {
    $result = $this->search($node);
    if ($result && $result['hits']['total'] !== 1) {
      return self::OPERATION_NOT_APPLICABLE;
    }

    $result = $this->em->deleteDocument($node->getType(), $result['hits']['hits'][0]['_id']);
    if ($result && $result['found'] !== true) {
      return self::OPERATION_ERROR;
    }

    return self::OPERATION_SUCCESS;
  }

  /**
    * Index all nodes from the index.
    *
    * @return array
    *   Success and failure amouts:
    *   array(
    *     'success' => 41,
    *     'failure' => 2
    *   )
    */
  public function indexAll()
  {
    $results = array(
      'success' => 0,
      'failure' => 0
    );

    // Recreate the index
    $this->em->closeIndex();
    $this->em->deleteIndex();

    $settings = self::getDefaultSettings();

    // Get enabled types
    $configTypes = \Drupal::config('elasticsearch_manager.types');
    $mappings = array(
      self::UNIQUE_TYPE => array(
        'properties' => array()
      )
    );
    foreach ($configTypes->getRawData() as $id => $active) {
      if ($active) {
        $type = NodeType::load($id);
        $mappings[self::UNIQUE_TYPE]['properties']['type'] = array(
          'type' => 'text',
          'fielddata' => true // Allow facets
        );

        // Get indexable fields for this type and create mapping
        $configFields = \Drupal::config('elasticsearch_manager.mapping');
        $definitions = \Drupal::entityManager()->getFieldDefinitions('node', $type->id());
        foreach ($definitions as $definition) {
          $value = $configFields->get($type->id() .'.'. $definition->getName());
          if ($value && $value != 'ignored') {
            $mappings[self::UNIQUE_TYPE]['properties'][$definition->getName()] = MappingFieldFactory::create($value)->getDefinition();
          }
        }
      }
    }

    $this->em->createIndex($mappings, $settings);

    // Reopen the index
    $this->em->openIndex();

    // Index data
    $types_names = array();
    foreach ($configTypes->getRawData() as $id => $active) {
      if ($active) {
        $type = NodeType::load($id);
        $typeResults = $this->indexAllFromType($type->id());
        $results['success'] += $typeResults['success'];
        $results['failure'] += $typeResults['failure'];
      }
    }

    return $results;
  }

  /**
    * Index all nodes with a type from the index.
    *
    * @param string $type
    *   The type that needs to be fully indexed.
    *
    * @return array
    *   Success and failure amouts:
    *   array(
    *     'success' => 41,
    *     'failure' => 2
    *   )
    */
  public function indexAllFromType($type)
  {
    $results = array(
      'success' => 0,
      'failure' => 0
    );

    // Search for all nodes from type
    $query = \Drupal::entityQuery('node')
      ->condition('type', $type)
      ->condition('status', NODE_PUBLISHED);
    $nids = $query->execute();

    // Index all nodes
    $nodes = \Drupal::entityManager()->getStorage('node')->loadMultiple($nids);
    foreach ($nodes as $node) {
      if ($this->insert($node)) {
        $results['success']++;
      } else {
        $results['failure']++;
      }
    }

    return $results;
  }


  /**
  * Prepare node data for indexation
  *
  * @param \Drupal\node\Entity\Node $node
  */
  public function prepareNode($node)
  {
    if (!is_object($node)) {
      return;
    }

    $params = array(
      'type' => $node->getType()
    );

    $config = \Drupal::config('elasticsearch_manager.mapping');

    // Add fields
    $fields = $node->getFields();
    foreach ($fields as $field) {
      $definition = $field->getFieldDefinition();

      $mapping = $config->get($node->getType() .'.'. $definition->getName());
      if (!$mapping || $mapping == 'ignored') {
        continue;
      }

      if ($field->getValue()) {
        if ($definition->getFieldStorageDefinition()->isMultiple()) {
          $params[$definition->getName()] = array_map(function($fieldValue) use ($definition) {
            return $this->prepareFieldValue($definition->getType(), $fieldValue);
          }, $field->getValue());
        } else {
          $params[$definition->getName()] = $this->prepareFieldValue($definition->getType(), $field->getValue()[0]);
        }
      }
    }

    return $params;
  }

  /**
  * Prepare field value for indexation
  *
  * @param string $type
  * @param array  $rawValue
  */
  private function prepareFieldValue($type, $rawValue)
  {
    $value = null;

    switch ($type) {
      case 'boolean':
        if (!is_string($rawValue['value'])) {
          $value = (boolean) $rawValue['value'];
        } else {
          $value = in_array(strtolower($rawValue['value']), array('1', 'true'));
        }
        break;
      case 'changed':
      case 'created':
        $value = date("Y-m-d H:i:s", $rawValue['value']);
        break;
      case 'datetime':
        $value = date("Y-m-d H:i:s", strtotime($rawValue['value']));
        break;
      case 'entity_reference':
        $value = intval($rawValue['target_id']);
        break;
      case 'image':
        $fid = intval($rawValue['target_id']);
        if ($file = File::load($fid)) {
          $value = $file->getFileUri();
        } else {
          $value = null;
        }
        break;
      case 'integer':
      case 'weight':
        $value = intval($rawValue['value']);
        break;
      case 'language':
        $value = $rawValue['value'];
        break;
      case 'link':
        $value = $rawValue['uri'];
        break;
      case 'path':
        $value = null;
        if (isset($rawValue['alias']) && $rawValue['alias']) {
          $value = $rawValue['alias'];
        } elseif (isset($rawValue['source'])) {
          $value = $rawValue['source'];
        }
        break;
      case 'string':
        $value = $rawValue['value'];
        break;
      case 'string_long':
      case 'text_long':
      case 'text_with_summary':
        $value = html_entity_decode(strip_tags($rawValue['value']), ENT_COMPAT | ENT_HTML5);
        break;
      case 'timestamp':
        $value = $rawValue['value'];
        break;
      case 'uuid':
        $value = $rawValue['value'];
        break;
      case 'youtube':
        $value = $rawValue['video_id'] ? $rawValue['video_id'] : $rawValue['input'];
        break;
      default:
        drupal_set_message(sprintf(t('The "%s" type is not recognized by NodeIndexer.', array(), array('context' => 'elasticsearch_manager')), $type), 'warning');
        if (isset($rawValue['value'])) {
          $value = $rawValue['value'];
        }
    }

    return $value;
  }

}
