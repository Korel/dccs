<?php

namespace Drupal\devel_generate;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Random;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

abstract class DevelGenerateBase extends PluginBase implements DevelGenerateBaseInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The plugin settings.
   *
   * @var array
   */
  protected $settings = array();

  /**
   * The random data generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  protected $random;

  /**
   * Implements Drupal\devel_generate\DevelGenerateBaseInterface::getSetting().
   */
  public function getSetting($key) {
    // Merge defaults if we have no value for the key.
    if (!array_key_exists($key, $this->settings)) {
      $this->settings = $this->getDefaultSettings();
    }
    return isset($this->settings[$key]) ? $this->settings[$key] : NULL;
  }

  /**
   * Implements Drupal\devel_generate\DevelGenerateBaseInterface::getDefaultSettings().
   */
  public function getDefaultSettings() {
    $definition = $this->getPluginDefinition();
    return $definition['settings'];
  }

  /**
   * Implements Drupal\devel_generate\DevelGenerateBaseInterface::getSettings().
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * Implements Drupal\devel_generate\DevelGenerateBaseInterface::settingsForm().
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return array();
  }

  /**
   * Implements Drupal\devel_generate\DevelGenerateBaseInterface::generate().
   */
  public function generate(array $values) {
    $this->generateElements($values);
    $this->setMessage('Generate process complete.');
  }

  /**
   * Business logic relating with each DevelGenerate plugin
   *
   * @param array $values
   *   The input values from the settings form.
   */
  protected function generateElements(array $values) {

  }

  /**
   * Populate the fields on a given entity with sample values.
   *
   * @param $entity
   *  The entity to be enriched with sample field values.
   */
  public static function populateFields(EntityInterface $entity) {
    $instances = entity_load_multiple_by_properties('field_config', array('entity_type' => $entity->getEntityType()->id(), 'bundle' => $entity->bundle()));
    if ($skips = function_exists('drush_get_option') ? drush_get_option('skip-fields', '') : @$_REQUEST['skip-fields']) {
      foreach (explode(',', $skips) as $skip) {
        unset($instances[$skip]);
      }
    }

    foreach ($instances as $instance) {
      /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
      $field_storage = $instance->getFieldStorageDefinition();
      $max = $cardinality = $field_storage->getCardinality();
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
        // Just an arbitrary number for 'unlimited'
        $max = rand(1, 3);
      }
      $field_name = $field_storage->getName();
      $entity->$field_name->generateSampleItems($max);
    }
  }

  /**
   * Implements Drupal\devel_generate\DevelGenerateBaseInterface::handleDrushValues().
   */
  public function handleDrushParams($args) {

  }

  /**
   * Set a message for either drush or the web interface.
   *
   * @param $msg
   *  The message to display.
   * @param $type
   *  The message type, as defined by drupal_set_message().
   *
   * @return
   *  Context-appropriate message output.
   */
  protected function setMessage($msg, $type = 'status') {
    $function = function_exists('drush_log') ? 'drush_log' : 'drupal_set_message';
    $function($msg, $type);
  }

  /**
   * Check if a given param is a number
   * @Return Boolean
   */
  public static function isNumber($number) {
    if ($number == NULL) return FALSE;
    if (!is_numeric($number)) return FALSE;
    return TRUE;
  }

  /**
   * @return \Drupal\Component\Utility\Random
   *   The random data generator.
   */
  protected function getRandom() {
    if(!$this->random) {
      $this->random = new Random();
    }
    return $this->random;
  }
}
