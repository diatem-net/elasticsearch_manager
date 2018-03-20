<?php

namespace Drupal\elasticsearch_manager\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\node\Entity\NodeType;

/**
 * Defines dynamic routes.
 */
class RouteProvider
{

  public function routes()
  {
    $route_collection = new RouteCollection();

    // Local tasks
    $config = \Drupal::config('elasticsearch_manager.types');
    foreach ($config->getRawData() as $id => $active) {
      if ($active) {
        $type =  NodeType::load($id);
        if ($type) {
          $route_collection->add(sprintf('elasticsearch_manager.mapping.%s', $type->id()), new Route(
            sprintf('/admin/config/elasticsearch-manager/mapping/%s', $type->id()),
            array(
              '_form' => '\\Drupal\\elasticsearch_manager\\Form\\MappingForm',
              '_title' => $type->label()
            ),
            array(
              '_permission'  => 'administer elasticsearch_manager',
            )
          ));
        }
      }
    }

    return $route_collection;
  }

}
