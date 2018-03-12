<?php

namespace Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type;

use Drupal\elasticsearch_manager\ElasticSearch\MappingField\MappingFieldInterface;

class TypeStringFrench implements MappingFieldInterface
{

  /**
    * {@inheritdoc}
    */
  public function getKey() {
    return 'string_french';
  }

  /**
    * {@inheritdoc}
    */
  public function getName() {
    return t('String (french)', array(), array('context' => 'elasticsearch_manager'));
  }

  /**
    * {@inheritdoc}
    */
  public function getDefinition() {
    return array(
      'type'     => 'text',
      'analyzer' => 'french_analyzer'
    );
  }

}
