<?php

namespace Drupal\elasticsearch_manager\ElasticSearch\MappingField;

interface MappingFieldInterface
{

  /**
    * Get mapping type key, for identification purpose
    * @return string
    */
  public function getKey();

  /**
    * Get mapping type name, for display purpose
    * @return string
    */
  public function getName();

  /**
    * Get mapping type definition
    * @return array
    */
  public function getDefinition();

}
