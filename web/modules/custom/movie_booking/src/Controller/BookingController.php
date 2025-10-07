<?php

namespace Drupal\movie_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class BookingController extends ControllerBase {

  public function customerBookings() {
    $build = [];

    $current_user = $this->currentUser();
    $uid = $current_user->id();

    // Fetch all booking nodes for this user
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'booking')
      ->condition('field_customer', $uid)
      ->sort('created', 'DESC')
      ->accessCheck(TRUE)
      ->execute();

    if (!empty($nids)) {
      $bookings = Node::loadMultiple($nids);
      $rows = [];

      foreach ($bookings as $booking) {
        // Movie title
        $movie_title = '';
        if (!$booking->get('field_movie')->isEmpty()) {
          $movie_node = Node::load($booking->get('field_movie')->target_id);
          if ($movie_node) {
            $movie_title = $movie_node->getTitle();
          }
        }

        $rows[] = [
          'movie' => $movie_title,
          'seats' => $booking->get('field_seat_number')->value ?? '',
          'showtime' => $booking->get('field_showtime')->value ?? '',
          'booked_on' => date('d M Y, h:i A', $booking->getCreatedTime()),
        ];
      }

      // Render table
      $header = [
        'movie' => $this->t('Movie'),
        'seats' => $this->t('Seats'),
        'showtime' => $this->t('Showtime'),
        'booked_on' => $this->t('Booked On'),
      ];

      $build['bookings_table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('You have not booked any movies yet.'),
        '#attributes' => ['class' => ['table', 'table-striped']],
        '#cache' => ['max-age' => 0],
      ];
    }
    else {
      $build['message'] = [
        '#markup' => '<p>You have not booked any movies yet.</p>',
      ];
    }

    return $build;
  }
}
