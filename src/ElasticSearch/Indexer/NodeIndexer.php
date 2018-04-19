<?php

namespace Drupal\elasticsearch_manager\ElasticSearch\Indexer;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\file\Entity\File;
use Drupal\elasticsearch_manager\Form\ConfigForm;
use Drupal\elasticsearch_manager\ElasticSearch\ElasticSearchManager;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\MappingFieldFactory;

class NodeIndexer
{

  const OPERATION_CANCELLED = -1;
  const OPERATION_ERROR = 0;
  const OPERATION_NOT_APPLICABLE = 1;
  const OPERATION_SUCCESS = 2;

  /**
   * ElasticSearch manager.
   *
   * @var \Drupal\elasticsearch_manager\ElasticSearch\ElasticSearchManager
   */
  protected $em;

  /**
   * ElasticSearch configuration
   *
   * @var array
   */
  protected $config;

  public function __construct(ElasticSearchManager $em)
  {
    $this->em = $em;
    $this->config = array(
      'types' => \Drupal::config('elasticsearch_manager.types'),
      'mapping' => \Drupal::config('elasticsearch_manager.mapping')
    );
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
   * Insert a new node in the index.
   *
   * @param Node $node
   *   The node that needs to be inserted.
   *
   * @return integer
   *   Operation's success.
   */
  public function insert($node)
  {
    $params = self::prepareNode($node, $this->config);
    if (!$params) {
      return self::OPERATION_NOT_APPLICABLE;
    }

    try {
      $result = $this->em->indexDocument(ConfigForm::DEFAULT_TYPE, $node->id(), $params);
    } catch (\Exception $e) {
      return self::OPERATION_ERROR;
    }

    return self::OPERATION_SUCCESS;
  }

  /**
   * Delete a new node from the index.
   *
   * @param Node $node
   *   The node that needs to be deleted.
   *
   * @return integer
   *   Operation's success.
   */
  public function delete($node)
  {
    try {
      $result = $this->em->deleteDocument(ConfigForm::DEFAULT_TYPE, $node->id());
    } catch (\Exception $e) {
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

    // Get all available languages
    $langs = \Drupal::languageManager()->getLanguages();
    $defaultLang = \Drupal::languageManager()->getdefaultLanguage();

    // Get enabled types
    $configTypes = $this->config['types'];
    $mappings = array(
      ConfigForm::DEFAULT_TYPE => array(
        'properties' => array()
      )
    );
    foreach ($configTypes->getRawData() as $id => $active) {
      if ($active) {
        $type = NodeType::load($id);
        $mappings[ConfigForm::DEFAULT_TYPE]['properties']['type'] = array(
          'type' => 'text',
          'fielddata' => true // Allow facets
        );

        // Get indexable fields for this type and create mapping
        $configFields = $this->config['mapping'];
        $definitions = \Drupal::entityManager()->getFieldDefinitions('node', $type->id());
        foreach ($definitions as $definition) {
          $value = $configFields->get(sprintf('%s.%s', $type->id(), $definition->getName()));
          if ($value && $value != MappingFieldFactory::IGNORED) {
            $mappings[ConfigForm::DEFAULT_TYPE]['properties'][$definition->getName()] = MappingFieldFactory::create($value)->getDefinition($defaultLang);

            // Multi-language field
            if ($value == MappingFieldFactory::TYPE_STRING_MULTILANG && $definition->isTranslatable()) {
              foreach ($langs as $lang) {
                $mappings[ConfigForm::DEFAULT_TYPE]['properties'][sprintf('%s__%s', $definition->getName(), $lang->getId())] = MappingFieldFactory::create($value)->getDefinition($lang);
              }
            }
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
        $typeResults = $this->indexAllFromType($id);
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
    $max_execution_time = ini_set('max_execution_time', -1);

    $results = array(
      'success' => 0,
      'failure' => 0
    );

    // Count all nodes from type
    $query = \Drupal::entityQuery('node')
      ->condition('type', $type)
      ->condition('status', NODE_PUBLISHED);
    $nids = $query->execute();
    $total = count($nids);

    $batchSize = (int) \Drupal::config('elasticsearch_manager.settings')->get('batch_size');
    if (!$batchSize) {
      $batchSize = ConfigForm::DEFAULT_BATCH_SIZE;;
    }

    // Index all nodes, by batch
    $client = new \GuzzleHttp\Client();
    for ($i = 0; $i < $total; $i += $batchSize) {
      $url = Url::fromRoute(
        'elasticsearch_manager.api.batch',
        array(
          'type' => $type,
          'from' => $i
        ),
        array('absolute' => true)
      );
      $response = $client->get($url->toString());
      $localResults = json_decode($response->getBody(), true);
      $results['success'] += $localResults['success'];
      $results['failure'] += $localResults['failure'];
    }

    ini_set('max_execution_time', $max_execution_time);

    return $results;
  }

  /**
   * Prepare node data for indexation
   *
   * @param Node $node
   *   The node to be indexed or updated
   *
   * @param array $config
   *   Elasticsearch config (avoid non-static calls)
   *
   * @return array
   *   Node description ready to send to ElasticSearch
   */
  protected static function prepareNode($node, $config)
  {
    if (!($node instanceof Node)) {
      return;
    }

    $params = array(
      'type'   => $node->getType()
    );

    // Get all available languages, and node translations
    $langs = \Drupal::languageManager()->getLanguages();
    $defaultLang = \Drupal::languageManager()->getdefaultLanguage();
    $languages = $node->getTranslationLanguages();
    $translations = array();
    foreach ($languages as $langcode => $language) {
      $translation = $node->getTranslation($langcode);
      $translations[$langcode] = $translation->getFields();
    }

    $fields = $node->getFields();

    $definitions = $node->getFieldDefinitions();
    foreach ($definitions as $definition) {

      $mapping = $config['mapping']->get(sprintf('%s.%s', $node->getType(), $definition->getName()));
      if (!$mapping || $mapping == MappingFieldFactory::IGNORED) {
        continue;
      }

      // Multi-language field
      if ($definition->isTranslatable()) {
        foreach ($translations as $langcode => $translation) {
          $field = $translation[$definition->getName()];
          if ($field->getValue()) {
            if ($definition->getFieldStorageDefinition()->isMultiple()) {
              $params[sprintf('%s__%s', $definition->getName(), $langcode)] = array_map(function($fieldValue) use ($definition) {
                return self::prepareFieldValue($definition->getType(), $fieldValue);
              }, $field->getValue());
            } else {
              $params[sprintf('%s__%s', $definition->getName(), $langcode)] = self::prepareFieldValue($definition->getType(), $field->getValue()[0]);
            }
          }
        }
      }

      // Single-language field
      else {
        $field = $fields[$definition->getName()];
        if ($field->getValue()) {
          if ($definition->getFieldStorageDefinition()->isMultiple()) {
            $params[$definition->getName()] = array_map(function($fieldValue) use ($definition) {
              return self::prepareFieldValue($definition->getType(), $fieldValue);
            }, $field->getValue());
          } else {
            $params[$definition->getName()] = self::prepareFieldValue($definition->getType(), $field->getValue()[0]);
          }
        }
      }

    }

    return $params;
  }

  /**
   * Prepare field value for indexation
   *
   * @param string $type
   *   Field type to be prepared.
   *
   * @param array  $rawValue
   *   Raw value of the field, may be updated to fit to ElasticSearch needs
   *
   * @return array
   *   Field description ready to send to ElasticSearch
   */
  protected static function prepareFieldValue($type, $rawValue)
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
