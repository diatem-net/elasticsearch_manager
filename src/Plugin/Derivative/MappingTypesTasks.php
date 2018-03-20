<?php

namespace Drupal\elasticsearch_manager\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\node\Entity\NodeType;

/**
 * Defines dynamic tasks for mappings.
 */
class MappingTypesTasks extends DeriverBase
{

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
        if ($type) {
          $route_name = sprintf('elasticsearch_manager.mapping.%s', $type->id());
          if (is_null($base_route)) {
            $base_route = $route_name;
          }

          $this->derivatives[$route_name] = $base_plugin_definition;
          $this->derivatives[$route_name]['title'] = $type->label();
          $this->derivatives[$route_name]['route_name'] = $route_name;
          $this->derivatives[$route_name]['base_route'] = $base_route;
          $this->derivatives[$route_name]['parent_id'] = 'elasticsearch_manager.mapping_tab';
        }
      }
    }

    return $this->derivatives;
  }

}
