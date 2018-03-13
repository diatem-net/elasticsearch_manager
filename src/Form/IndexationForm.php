<?php

namespace Drupal\elasticsearch_manager\Form;

use Drupal\node\Entity\NodeType;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_manager\ElasticSearch\ElasticSearchManager;
use Drupal\elasticsearch_manager\ElasticSearch\Indexer\NodeIndexer;

class IndexationForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'elasticsearch_manager_indexation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $form['description'] = array(
      '#markup' => '<p>' . t('Indexation will first delete a type before indexing its documents.', array(), array('context' => 'elasticsearch_manager')) . '</p>',
    );

    $config = \Drupal::config('elasticsearch_manager.types');

    $types_names = array();
    foreach ($config->getRawData() as $id => $active) {
      if ($active) {
        $type = NodeType::load($id);
        $types_names[$type->id()] = $type->label();
      }
    }
    $form['type'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Types', array(), array('context' => 'elasticsearch_manager')),
      '#description' => t('Types to be indexed. Will be ignored if "All types" is checked.', array(), array('context' => 'elasticsearch_manager')),
      '#options' => $types_names,
      '#default_value' => array()
    );

    $form['all_types'] = array(
      '#type' => 'checkboxes',
      '#title' => null,
      '#options' => array(
        'all_types' => t('Index all types?', array(), array('context' => 'elasticsearch_manager'))
      ),
      '#default_value' => array()
    );

    $form['run'] = array(
      '#type' => 'submit',
      '#value' => t('Run indexation', array(), array('context' => 'elasticsearch_manager'))
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $em = new ElasticSearchManager();
    $nodeIndexer = new NodeIndexer($em);

    $results = array(
        'success' => 0,
        'failure' => 0
    );

    $all_types = $form_state->getValue('all_types');
    if ($all_types['all_types'] !== 0) {
      $results = $nodeIndexer->indexAll();
    } else {
      $types = $form_state->getValue('type');
      foreach ($types as $key => $value) {
        if ($value !== 0) {
          $typeResults = $nodeIndexer->indexAllFromType($value);
          $results['success'] += $typeResults['success'];
          $results['failure'] += $typeResults['failure'];
        }
      }
    }

    $status = 'status';
    if ($results['failure'] > 0) {
      $status = 'warning';
      if ($results['success'] == 0) {
        $status = 'error';
      }
    }
    drupal_set_message(sprintf(
      t('%u / %u documents have been indexed.', array(), array('context' => 'elasticsearch_manager')),
      $results['success'],
      $results['success'] + $results['failure']
    ), $status);

  }

}
