<?php

namespace Drupal\elasticsearch_manager\ElasticSearch\MappingField;

use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeBoolean;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeDate;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeInteger;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeString;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeStringFrench;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeStringKeyword;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeEntityReference;

class MappingFieldFactory
{

  public static function create($key)
  {
    switch ($key) {
      case 'boolean':
        return new TypeBoolean();
        break;
      case 'date':
        return new TypeDate();
        break;
      case 'integer':
        return new TypeInteger();
        break;
      case 'string':
        return new TypeString();
        break;
      case 'string_french':
        return new TypeStringFrench();
        break;
      case 'string_keyword':
        return new TypeStringKeyword();
        break;
      case 'entity_reference':
        return new TypeEntityReference();
        break;
      default:
        throw new MappingFieldCreationException(sprintf(
          t('Creation of "%s" impossible.', array(), array('context' => 'elasticsearch_manager')),
          $key
        ));
    }
  }

  public static function getDefaultDefinition()
  {
    return array(
      'analyzer'        => 'nGram_analyzer',
      'search_analyzer' => 'whitespace_analyzer'
    );
  }

}
