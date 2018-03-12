<?php

namespace Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type;

use Drupal\elasticsearch_manager\ElasticSearch\MappingField\MappingFieldInterface;

class TypeEntityReference implements MappingFieldInterface
{

  /**
    * {@inheritdoc}
    */
  public function getKey() {
    return 'entity_reference';
  }

  /**
    * {@inheritdoc}
    */
  public function getName() {
    return t('Entity reference', array(), array('context' => 'elasticsearch_manager'));
  }

  /**
    * {@inheritdoc}
    */
  public function getDefinition() {
    return array(
      'type' => 'integer'
    );
  }

}
