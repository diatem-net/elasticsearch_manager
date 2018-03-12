<?php

namespace Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type;

use Drupal\elasticsearch_manager\ElasticSearch\MappingField\MappingFieldInterface;

class TypeStringKeyword implements MappingFieldInterface
{

  /**
    * {@inheritdoc}
    */
  public function getKey() {
    return 'string_keyword';
  }

  /**
    * {@inheritdoc}
    */
  public function getName() {
    return t('String (keyword)', array(), array('context' => 'elasticsearch_manager'));
  }

  /**
    * {@inheritdoc}
    */
  public function getDefinition() {
    return array(
      'type' => 'keyword'
    );
  }

}
