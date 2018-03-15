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

    // General tasks
    $route_collection->add('elasticsearch_manager.config', new Route(
      '/admin/config/elasticsearch-manager/config',
      array(
        '_form' => '\\Drupal\\elasticsearch_manager\\Form\\ConfigForm',
        '_title' => 'ElasticSearch Manager'
      ),
      array(
        '_permission'  => 'administer elasticsearch_manager',
      )
    ));

    $route_collection->add('elasticsearch_manager.types', new Route(
      '/admin/config/elasticsearch-manager/types',
      array(
        '_form' => '\\Drupal\\elasticsearch_manager\\Form\\TypesForm',
        '_title' => 'ElasticSearch Manager'
      ),
      array(
        '_permission'  => 'administer elasticsearch_manager',
      )
    ));

    $route_collection->add('elasticsearch_manager.mapping', new Route(
      '/admin/config/elasticsearch-manager/mapping',
      array(
        '_form' => '\\Drupal\\elasticsearch_manager\\Form\\MappingForm',
        '_title' => 'ElasticSearch Manager'
      ),
      array(
        '_permission'  => 'administer elasticsearch_manager',
      )
    ));

    $route_collection->add('elasticsearch_manager.indexation', new Route(
      '/admin/config/elasticsearch-manager/indexation',
      array(
        '_form' => '\\Drupal\\elasticsearch_manager\\Form\\IndexationForm',
        '_title' => 'ElasticSearch Manager'
      ),
      array(
        '_permission'  => 'administer elasticsearch_manager',
      )
    ));

    // Local tasks
    $config = \Drupal::config('elasticsearch_manager.types');
    foreach ($config->getRawData() as $id => $active) {
      if ($active) {
        $type =  NodeType::load($id);
        if ($type) {
          $route_collection->add('elasticsearch_manager.mapping.'. $type->id(), new Route(
            '/admin/config/elasticsearch-manager/mapping/'. $type->id(),
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
