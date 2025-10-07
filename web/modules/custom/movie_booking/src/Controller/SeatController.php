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

    if ($node->hasField('field_seat_map') && !$node->get('field_seat_map')->isEmpty()) {
        $seat_map_json = $node->get('field_seat_map')->value;
        $seat_map = json_decode($seat_map_json, TRUE);

        if (!is_array($seat_map)) {
            return new JsonResponse(['markup' => '<p>Invalid seat map data.</p>']);
        }

        $markup .= '<h5>' . htmlspecialchars($node->getTitle()) . ' - Seat Map</h5>';
        $markup .= '<div class="seat-map d-flex flex-column gap-2">';

        foreach ($seat_map as $row_label => $seats) {
            if (!is_array($seats)) continue;

            // Row container with flex display
            $markup .= '<div class="seat-row d-flex gap-2 mb-2">';

            foreach ($seats as $seat) {
                if (!is_array($seat) || !isset($seat['label'], $seat['status'])) continue;
 
                $status_class = $seat['status'] === 'sold' ? 'btn-danger' : 'btn-success';
                $markup .= '<button class="btn ' . $status_class . ' btn-sm" disabled>' . htmlspecialchars($seat['label']) . '</button>';
            }

            $markup .= '</div>'; // end row
        }

        $markup .= '</div>'; // end seat map
    }
    else {
        $markup = '<p>No seat map available for this movie.</p>';
    }

    return new JsonResponse(['markup' => $markup]);
}

}
