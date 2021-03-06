<?php

namespace Drupal\elasticsearch_manager\Form;

use Monolog\Logger;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_manager\ElasticSearch\ElasticSearchManager;

class ConfigForm extends ConfigFormBase
{

  const DEFAULT_BATCH_SIZE = 500;
  const DEFAULT_TYPE = 'node';

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
      '#description' => t('Elasticsearch host to use for indexation and search.<br><i>Must include scheme and port.</i>', array(), array('context' => 'elasticsearch_manager')),
      '#default_value' => $config->get('host') ?: 'http://127.0.0.1:9200'
    );
    $form['index'] = array(
      '#type' => 'textfield',
      '#title' => t('Index', array(), array('context' => 'elasticsearch_manager')),
      '#description' => t('Index to use for indexation and search.', array(), array('context' => 'elasticsearch_manager')),
      '#default_value' => $config->get('index')
    );
    $form['logs'] = array(
      '#type' => 'select',
      '#title' => t('Debug', array(), array('context' => 'elasticsearch_manager')),
      '#description' => sprintf(t('Logs will be sent to <i>%s</i>', array(), array('context' => 'elasticsearch_manager')), ElasticSearchManager::getLogFile()),
      '#options' => array(0 => 'DISABLED') + array_flip(Logger::getLevels()),
      '#default_value' => $config->get('logs') ?: 0,
    );
    $form['batch_size'] = array(
      '#type' => 'number',
      '#title' => t('Batch size', array(), array('context' => 'elasticsearch_manager')),
      '#description' => t('The higher the value is, the faster the indexation will be.<br><i>Warning: a value too high may provoke out-of-memory errors.</i>', array(), array('context' => 'elasticsearch_manager')),
      '#default_value' => $config->get('batch_size') ?: self::DEFAULT_BATCH_SIZE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $config = $this->config('elasticsearch_manager.settings');
    $config->set('host', rtrim($form_state->getValue('host'), '/'));
    $config->set('index', $form_state->getValue('index'));
    $config->set('logs', $form_state->getValue('logs'));
    $config->set('batch_size', $form_state->getValue('batch_size'));
    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
      'elasticsearch_manager.settings',
    ];
  }

}
