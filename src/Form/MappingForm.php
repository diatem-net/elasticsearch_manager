<?php

namespace Drupal\elasticsearch_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_manager\ElasticSearch\MappingField\MappingFieldFactory;

class MappingForm extends ConfigFormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'elasticsearch_manager_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('elasticsearch_manager.mapping');

    $route_name = \Drupal::service('current_route_match')->getRouteName();
    $type = preg_replace('/elasticsearch_manager\.mapping\./', '', $route_name);

    if ($type === 'elasticsearch_manager.mapping') {
      drupal_set_message(t('Please select the type to map', array(), array('context' => 'elasticsearch_manager')), 'warning');
      return $form;
    }

    $definitions = \Drupal::entityManager()->getFieldDefinitions('node', $type);
    if (empty($definitions)) {
      drupal_set_message(sprintf(t('The type "%s" is not recognized', array(), array('context' => 'elasticsearch_manager')), $type), 'error');
      return $form;
    }

    $mapping_options = array(
      'ignored'          => t('Ignored', array(), array('context' => 'elasticsearch_manager')),
      'boolean'          => MappingFieldFactory::create('boolean')->getName(),
      'date'             => MappingFieldFactory::create('date')->getName(),
      'integer'          => MappingFieldFactory::create('integer')->getName(),
      'string'           => MappingFieldFactory::create('string')->getName(),
      'string_french'    => MappingFieldFactory::create('string_french')->getName(),
      'string_keyword'   => MappingFieldFactory::create('string_keyword')->getName(),
      'entity_reference' => MappingFieldFactory::create('entity_reference')->getName()
    );

    $form['indexed'] = array(
      '#type'  => 'details',
      '#title' => t('Indexed fields', array(), array('context' => 'elasticsearch_manager')),
      '#open'  => true
    );

    $mapping_existing = array();
    foreach ($config->get() as $fields) {
      foreach ($fields as $key => $value) {
        if ($value !== 'ignored') {
          $mapping_existing[$key] = $value;
        }
      }
    }

    $indexed = 0;
    foreach ($definitions as $definition) {
      $value = $config->get(sprintf('%s.%s', $type, $definition->getName()));
      if ($value && $value != 'ignored') {
        $indexed++;

        $filtered_options = $mapping_options;
        if (isset($mapping_existing[$definition->getName()])) {
          $mapping = $mapping_existing[$definition->getName()];
          $filtered_options = array(
            'ignored' => $mapping_options['ignored'],
            $mapping  => $mapping_options[$mapping]
          );
        }

        $form['indexed'][sprintf('mapping_%s__%s', $type, $definition->getName())] = array(
          '#type'          => 'select',
          '#title'         => $definition->getName(),
          '#options'       => $filtered_options,
          '#default_value' => $value
        );
      }
    }

    if ($indexed == 0) {
      $form['indexed']['empty'] = array(
        '#markup' => t('No indexed fields for this type.', array(), array('context' => 'elasticsearch_manager')),
      );
    }

    $form['ignored'] = array(
      '#type'  => 'details',
      '#title' => t('Ignored fields', array(), array('context' => 'elasticsearch_manager')),
      '#open'  => false
    );

    $ignored = 0;
    foreach ($definitions as $definition) {
      $value = $config->get(sprintf('%s.%s', $type, $definition->getName()));
      if (!$value || $value == 'ignored') {
        $ignored++;

        $filtered_options = $mapping_options;
        if (isset($mapping_existing[$definition->getName()])) {
          $mapping = $mapping_existing[$definition->getName()];
          $filtered_options = array(
            'ignored' => $mapping_options['ignored'],
            $mapping  => $mapping_options[$mapping]
          );
        }

        $form['ignored'][sprintf('mapping_%s__%s', $type, $definition->getName())] = array(
          '#type'          => 'select',
          '#title'         => $definition->getName(),
          '#options'       => $filtered_options,
          '#default_value' => 'ignored'
        );
      }
    }

    if ($ignored == 0) {
      $form['ignored']['empty'] = array(
        '#markup' => t('No ignored fields for this type.', array(), array('context' => 'elasticsearch_manager')),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $config = $this->config('elasticsearch_manager.mapping');

    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      if (!preg_match('/^mapping_(?<type>.+)__(?<field>.+)$/', $key, $matches)) {
        continue;
      }
      $config->set(sprintf('%s.%s', $matches['type'], $matches['field']), $value);
    }

    $config->save();

    $return = parent::submitForm($form, $form_state);
    drupal_set_message(t('Don\'t forget to index the modified types, to take your new mapping in account.', array(), array('context' => 'elasticsearch_manager')), 'warning');
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
      'elasticsearch_manager.mapping'
    ];
  }

}
