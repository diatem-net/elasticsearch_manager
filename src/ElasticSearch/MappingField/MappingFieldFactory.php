<?php

namespace Drupal\elasticsearch_manager\ElasticSearch\MappingField;

use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeBoolean;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeDate;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeInteger;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeString;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeStringFrench;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeStringMultilang;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeStringKeyword;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type\TypeEntityReference;

class MappingFieldFactory
{

  const IGNORED               = 'ignored';

  const TYPE_BOOLEAN          = 'boolean';
  const TYPE_DATE             = 'date';
  const TYPE_INTEGER          = 'integer';
  const TYPE_STRING           = 'string';
  const TYPE_STRING_FRENCH    = 'string_french';
  const TYPE_STRING_MULTILANG = 'string_multilang';
  const TYPE_STRING_KEYWORD   = 'string_keyword';
  const TYPE_ENTITY_REFERENCE = 'entity_reference';

  public static function create($key)
  {
    switch ($key) {
      case self::TYPE_BOOLEAN:
        return new TypeBoolean();
        break;
      case self::TYPE_DATE:
        return new TypeDate();
        break;
      case self::TYPE_INTEGER:
        return new TypeInteger();
        break;
      case self::TYPE_STRING:
        return new TypeString();
        break;
      case self::TYPE_STRING_FRENCH:
        return new TypeStringFrench();
        break;
      case self::TYPE_STRING_MULTILANG:
        return new TypeStringMultilang();
        break;
      case self::TYPE_STRING_KEYWORD:
        return new TypeStringKeyword();
        break;
      case self::TYPE_ENTITY_REFERENCE:
        return new TypeEntityReference();
        break;
      default:
        throw new MappingFieldCreationException(sprintf(
          t('Creation of "%s" impossible.', array(), array('context' => 'elasticsearch_manager')),
          $key
        ));
    }
  }

}
