<?php

namespace Drupal\movie_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for seat-related AJAX actions.
 */
class SeatController extends ControllerBase {

  /**
   * AJAX callback to return seat map markup for a movie node.
   */
  public function seatMapAjax(Node $node) {
    $markup = '';

    if ($node->hasField('field_seats_map') && !$node->get('field_seats_map')->isEmpty()) {
      $seat_map_json = $node->get('field_seats_map')->value;
      $seat_map = json_decode($seat_map_json, TRUE);

      $markup .= '<h5>' . $node->getTitle() . ' - Seat Map</h5>';
      $markup .= '<div class="seat-map d-flex flex-column gap-2">';

      foreach ($seat_map as $row) {
        $markup .= '<div class="seat-row d-flex gap-2">';
        foreach ($row as $seat) {
          $status_class = ($seat['status'] === 'sold') ? 'btn-danger' : 'btn-success';
          $markup .= '<button class="btn ' . $status_class . '" disabled>' . $seat['label'] . '</button>';
        }
        $markup .= '</div>';
      }

      $markup .= '</div>';
    }
    else {
      $markup = '<p>No seat map available for this movie.</p>';
    }

    return new JsonResponse(['markup' => $markup]);
  }

}
