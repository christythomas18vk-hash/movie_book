<?php

namespace Drupal\movie_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Provides the landing page for the Movie Booking system.
 */
class LandingPageController extends ControllerBase {

  /**
   * Renders the landing page.
   */
  public function landingPage() {
    $build = [];

    // ✅ Attach Bootstrap CSS
    $build['#attached']['html_head'][] = [
      [
        '#tag' => 'link',
        '#attributes' => [
          'rel' => 'stylesheet',
          'href' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
        ],
      ],
      'bootstrap_cdn',
    ];

    // ✅ Header
    $header = '
      <header class="bg-dark text-white p-3">
        <div class="container d-flex justify-content-between align-items-center">
          <h2 class="m-0">🎬 Movie Booking</h2>
          <nav>
            <a href="/admin/login" class="btn btn-outline-light me-2">Admin</a>
            <a href="/customer/login" class="btn btn-outline-light me-2">Customer</a>
            <a href="/about-us" class="btn btn-outline-light me-2">About Us</a>
            <a href="/contact-us" class="btn btn-outline-light">Contact Us</a>
          </nav>
        </div>
      </header>
    ';

    // ✅ Fetch the movie with the highest rating
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'movies')
      ->sort('field_rating', 'DESC')
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();

    $movie_markup = '<div class="text-center my-5"><p>No movies available yet.</p></div>';

    if (!empty($nids)) {
      $nid = reset($nids);
      $movie = Node::load($nid);

      if ($movie) {
        $title = $movie->getTitle();
        $poster_url = '';
        $genre = '';
        $showtime = '';
        $ticket_price = $movie->get('field_ticket_price')->value ?? 'N/A';
        $rating = $movie->get('field_rating')->value ?? 'N/A';
        $total_seats = $movie->get('field_total_seats')->value ?? 'N/A';

        // Poster
        if (!$movie->get('field_poster')->isEmpty()) {
          $file = $movie->get('field_poster')->entity;
          if ($file) {
            $poster_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
          }
        }

        // Genre
        if (!$movie->get('field_genre')->isEmpty()) {
          $term = $movie->get('field_genre')->entity;
          if ($term) {
            $genre = $term->label();
          }
        }

        // Showtime
        if (!$movie->get('field_showtimes')->isEmpty()) {
          $showtime = date('d M Y, h:i A', strtotime($movie->get('field_showtimes')->value));
        }

        // Build markup for the top-rated movie
        $movie_markup = '
          <main class="container my-5 text-center">
            <h1 class="display-5 fw-bold mb-4">🎥 Top Rated Movie</h1>
            <div class="card shadow mx-auto" style="max-width: 600px;">
              ' . ($poster_url ? '<img src="' . $poster_url . '" class="card-img-top" alt="' . $title . '">' : '') . '
              <div class="card-body">
                <h3 class="card-title fw-bold">' . $title . '</h3>
                <p class="card-text"><strong>🎭 Genre:</strong> ' . $genre . '</p>
                <p class="card-text"><strong>⭐ Rating:</strong> ' . $rating . '</p>
                <p class="card-text"><strong>🕒 Showtime:</strong> ' . $showtime . '</p>
                <p class="card-text"><strong>💺 Total Seats:</strong> ' . $total_seats . '</p>
                <p class="card-text text-success fw-semibold"><strong>🎟 Ticket Price:</strong> ₹' . $ticket_price . '</p>
                <a href="/customer/login" class="btn btn-success btn-lg mt-3">🎫 Book Now</a>
              </div>
            </div>
          </main>
        ';
      }
    }

    // ✅ Footer
    $footer = '
      <footer class="bg-light text-center py-3 mt-5 border-top">
        <p class="mb-0">© ' . date('Y') . ' Movie Booking. All rights reserved.</p>
      </footer>
    ';

    // ✅ Combine all parts
    $build['#markup'] = $header . $movie_markup . $footer;

    return $build;
  }

}
