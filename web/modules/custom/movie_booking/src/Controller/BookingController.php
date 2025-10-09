<?php

namespace Drupal\movie_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

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

    $rows = [];
    if (!empty($nids)) {
      $bookings = Node::loadMultiple($nids);

      foreach ($bookings as $booking) {
        // Get Movie title
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
    }

    // Header with back button
    $build['header'] = [
      '#markup' => '
        <div class="container my-5 text-start">
          <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
              <h1 class="display-5 fw-bold mb-0" style="margin: 0;">🎟️ My Bookings</h1>
            </div>
          </div>
          <div class="d-flex align-items-right justify-content-between mb-4">
            <a href="/customer/dashboard" 
              class="btn btn-secondary" 
              style="margin-bottom: 10px;">
              ← Back to Dashboard
            </a>
          </div>
        </div>',
    ];

    if (!empty($rows)) {
      // Render a styled table
      $build['bookings_table'] = [
        '#markup' => $this->buildStyledTable($rows),
        '#cache' => ['max-age' => 0],
      ];
    }
    else {
      $build['message'] = [
        '#markup' => '
          <div class="container text-center py-5">
            <h4 class="text-muted">You have not booked any movies yet.</h4>
            <a href="/customer/dashboard" class="btn btn-primary mt-3">
              Browse Movies 🎬
            </a>
          </div>',
        '#cache' => ['max-age' => 0],
      ];
    }

    // Attach CSS
    $build['#attached']['html_head'][] = [
      [
        '#tag' => 'style',
        '#value' => '
          .bookings-table-wrapper {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
          }
          table.bookings-table {
            width: 100%;
            border-collapse: collapse;
          }
          table.bookings-table thead {
            background: linear-gradient(90deg, #007bff, #00bcd4);
            color: white;
            font-weight: 600;
          }
          table.bookings-table th, 
          table.bookings-table td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #eaeaea;
          }
          table.bookings-table tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.3s ease;
          }
          .movie-title {
            font-weight: 600;
            color: #343a40;
          }
          .seat-badge {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 4px 10px;
            border-radius: 5px;
            margin: 2px;
            font-size: 0.85rem;
          }
          a.btn-secondary {
            background-color: #6c757d;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
          }
          a.btn-secondary:hover {
            background-color: #5a6268;
          }
          @media (max-width: 768px) {
            table.bookings-table th, 
            table.bookings-table td {
              padding: 10px;
              font-size: 0.9rem;
            }
          }
        ',
      ],
      'custom_booking_table_styles',
    ];

    return $build;
  }

  /**
   * Helper function to build a styled HTML table
   */
  private function buildStyledTable(array $rows) {
    $html = '<div class="container bookings-table-wrapper">';
    $html .= '<table class="bookings-table">';
    $html .= '<thead><tr>
                <th>Movie</th>
                <th>Seats</th>
                <th>Showtime</th>
                <th>Booked On</th>
              </tr></thead><tbody>';

    foreach ($rows as $row) {
      $seats_html = '';
      foreach (explode(',', $row['seats']) as $seat) {
        $seats_html .= '<span class="seat-badge">' . trim($seat) . '</span>';
      }

      $html .= '<tr>
                  <td class="movie-title">' . htmlspecialchars($row['movie']) . '</td>
                  <td>' . $seats_html . '</td>
                  <td>' . htmlspecialchars($row['showtime']) . '</td>
                  <td>' . htmlspecialchars($row['booked_on']) . '</td>
                </tr>';
    }

    $html .= '</tbody></table></div>';
    return $html;
  }

}

