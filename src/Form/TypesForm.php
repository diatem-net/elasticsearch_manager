<?php

namespace Drupal\elasticsearch_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_manager\ElasticSearch\ElasticSearchManager;

class TypesForm extends ConfigFormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'elasticsearch_manager_types_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('elasticsearch_manager.types');

    $form['description'] = array(
      '#markup' => '<p>' . t('Choose the types that will be mapped.', array(), array('context' => 'elasticsearch_manager')) . '</p>',
    );

    $types = \Drupal::entityManager()->getStorage('node_type')->loadMultiple();
    $types_names   = array();
    $types_enabled = array();
    foreach ($types as $type) {
      $types_names[$type->id()] = $type->label();
      if ($c = $config->get($type->id())) {
        $types_enabled[] = $type->id();
      }
    }
    $form['type'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Types', array(), array('context' => 'elasticsearch_manager')),
      '#options' => $types_names,
      '#default_value' => $types_enabled
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $config = $this->config('elasticsearch_manager.types');

    $types = $form_state->getValue('type');
    foreach ($types as $key => $value) {
      if ($value !== 0) {
        $config->set($key, 1);
      } else {
        $config->set($key, 0);
      }
    }

    $config->save();

    $return = parent::submitForm($form, $form_state);
    drupal_set_message(t('Don\'t forget to define your mappings.', array(), array('context' => 'elasticsearch_manager')), 'warning');
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
      'elasticsearch_manager.types'
    ];
  }

}
