<?php

namespace Drupal\elasticsearch_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'elasticsearch_manager_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('elasticsearch_manager.settings');

    $form['host'] = array(
      '#type' => 'textfield',
      '#title' => t('Host', array(), array('context' => 'elasticsearch_manager')),
      '#description' => t('Elasticsearch host to use for indexation and search.', array(), array('context' => 'elasticsearch_manager')),
      '#default_value' => $config->get('host') ?: '127.0.0.1'
    );
    $form['port'] = array(
      '#type' => 'textfield',
      '#title' => t('Port', array(), array('context' => 'elasticsearch_manager')),
      '#description' => t('Port to use (9200 by default).', array(), array('context' => 'elasticsearch_manager')),
      '#default_value' => $config->get('port') ?: '9200'
    );
    $form['index'] = array(
      '#type' => 'textfield',
      '#title' => t('Index', array(), array('context' => 'elasticsearch_manager')),
      '#description' => t('Index to use for indexation and search.', array(), array('context' => 'elasticsearch_manager')),
      '#default_value' => $config->get('index')
    );
    $form['debug'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Debug', array(), array('context' => 'elasticsearch_manager')),
      '#description' => t('Enable to see Elastic queries\' JSON.', array(), array('context' => 'elasticsearch_manager')),
      '#options' => array('debug' => t('Enable', array(), array('context' => 'elasticsearch_manager'))),
      '#default_value' => $config->get('debug') ?: array(),
    );

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('elasticsearch_manager.settings');
    $config->set('host', $form_state->getValue('host'));
    $config->set('port', $form_state->getValue('port'));
    $config->set('index', $form_state->getValue('index'));
    $config->set('debug', $form_state->getValue('debug'));
    $config->save();

    return parent::submitForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {

    return [
      'elasticsearch_manager.settings',
    ];

  }

}
