<?php

namespace Drupal\elasticsearch_manager\ElasticSearch\MappingField\Type;

use Drupal\elasticsearch_manager\ElasticSearch\MappingField\MappingFieldInterface;
use Drupal\Core\Language\LanguageInterface;

class TypeStringMultilang implements MappingFieldInterface
{

  /**
    * {@inheritdoc}
    */
  public function getKey() {
    return 'string_multilang';
  }

  /**
    * {@inheritdoc}
    */
  public function getName() {
    return t('String (multilang)', array(), array('context' => 'elasticsearch_manager'));
  }

  /**
    * {@inheritdoc}
    */
  public function getDefinition(LanguageInterface $lang = null) {
    if ($lang) {
      switch ($lang->getId()) {
        case 'fr':
          return array(
            'type'     => 'text',
            'analyzer' => 'french_analyzer'
          );
          break;
        case 'de':
          return array(
            'type'     => 'text',
            'analyzer' => 'german'
          );
          break;
        case 'es':
          return array(
            'type'     => 'text',
            'analyzer' => 'spanish'
          );
          break;
        case 'it':
          return array(
            'type'     => 'text',
            'analyzer' => 'italian'
          );
          break;
        case 'pt':
          return array(
            'type'     => 'text',
            'analyzer' => 'portuguese'
          );
          break;
        default:
          return array(
            'type'     => 'text',
            'analyzer' => 'english'
          );
      }
    }

    return array(
      'type'     => 'text',
      'analyzer' => 'english'
    );
  }

}
