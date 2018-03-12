<?php

namespace Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type;

use Drupal\elasticsearch_manager\ElasticSearch\MappingField\MappingFieldInterface;

class TypeDate implements MappingFieldInterface
{

  /**
    * {@inheritdoc}
    */
  public function getKey() {
    return 'date';
  }

  /**
    * {@inheritdoc}
    */
  public function getName() {
    return t('Date', array(), array('context' => 'elasticsearch_manager'));
  }

  /**
    * {@inheritdoc}
    */
  public function getDefinition() {
    return array(
      'type'   => 'date',
      'format' => 'yyyy-MM-dd HH:mm:ss'
    );
  }

}
