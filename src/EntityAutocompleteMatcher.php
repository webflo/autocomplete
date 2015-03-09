<?php

/**
 * @file
 * Contains Drupal\autocomplete\EntityAutocompleteMatcher.
 */

namespace Drupal\autocomplete;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityAutocompleteMatcher as DefaultEntityAutocompleteMatcher;

/**
 * Class EntityAutocompleteMatcher.
 *
 * @package Drupal\autocomplete
 */
class EntityAutocompleteMatcher extends DefaultEntityAutocompleteMatcher {

  /**
   * Returns matched labels based on a given search string.
   *
   * @param string $target_type
   *   The ID of the target entity type.
   * @param string $selection_handler
   *   The plugin ID of the entity reference selection handler.
   * @param array $selection_settings
   *   An array of settings that will be passed to the selection handler.
   * @param string $string
   *   (optional) The label of the entity to query by.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the current user doesn't have access to the specifies entity.
   *
   * @return array
   *   An array of matched entity labels, in the format required by the AJAX
   *   autocomplete API (e.g. array('value' => $value, 'label' => $label)).
   *
   * @see \Drupal\system\Controller\EntityAutocompleteController
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    $matches = array();

    $options = array(
      'target_type' => $target_type,
      'handler' => $selection_handler,
      'handler_settings' => $selection_settings,
    );
    $handler = $this->selectionManager->getInstance($options);

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, $this->getLimit());
      $matches = $this->formatResult($entity_labels);
    }

    return $matches;
  }

  public function getLimit() {
    return 10;
  }

  public function formatResult($results) {
    $matches = array();

    // Loop through the entities and convert them into autocomplete output.
    foreach ($results as $values) {
      foreach ($values as $entity_id => $label) {
        $key = "$label ($entity_id)";
        // Strip things like starting/trailing white spaces, line breaks and
        // tags.
        $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(String::decodeEntities(strip_tags($key)))));
        // Names containing commas or quotes must be wrapped in quotes.
        $key = Tags::encode($key);
        $matches[] = array('value' => $key, 'label' => $label);
      }
    }

    return $matches;
  }

}
