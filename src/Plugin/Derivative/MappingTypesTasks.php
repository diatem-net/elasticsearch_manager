<?php

namespace Drupal\elasticsearch_manager\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\node\Entity\NodeType;

/**
 * Defines dynamic tasks for mappings.
 */
class MappingTypesTasks extends DeriverBase {

    /**
    * {@inheritdoc}
    */
    public function getDerivativeDefinitions($base_plugin_definition)
    {
      $config = \Drupal::config('elasticsearch_manager.types');

      $base_route = null;
      foreach ($config->getRawData() as $id => $active) {
        if ($active) {

          $type = NodeType::load($id);
          if (is_null($base_route)) {
            $base_route = 'elasticsearch_manager.mapping.'. $type->id();
          }

          $this->derivatives['elasticsearch_manager.mapping.'. $type->id()] = $base_plugin_definition;
          $this->derivatives['elasticsearch_manager.mapping.'. $type->id()]['title'] = $type->label();
          $this->derivatives['elasticsearch_manager.mapping.'. $type->id()]['route_name'] = 'elasticsearch_manager.mapping.'. $type->id();
          $this->derivatives['elasticsearch_manager.mapping.'. $type->id()]['base_route'] = $base_route;
          $this->derivatives['elasticsearch_manager.mapping.'. $type->id()]['parent_id'] = 'elasticsearch_manager.mapping_tab';

        }
      }

      return $this->derivatives;
    }

}
