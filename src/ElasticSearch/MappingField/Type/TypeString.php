<?php

namespace Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type;

use Drupal\elasticsearch_manager\ElasticSearch\MappingField\MappingFieldInterface;

class TypeString implements MappingFieldInterface
{

  /**
    * {@inheritdoc}
    */
  public function getKey() {
    return 'string';
  }

  /**
    * {@inheritdoc}
    */
  public function getName() {
    return t('String', array(), array('context' => 'elasticsearch_manager'));
  }

  /**
    * {@inheritdoc}
    */
  public function getDefinition() {
    return array(
      'type' => 'text'
    );
  }

}
