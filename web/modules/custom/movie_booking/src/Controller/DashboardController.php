<?php

namespace Drupal\movie_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Controller for admin and customer dashboards.
 */
class DashboardController extends ControllerBase {

  /**
   * Admin dashboard page.
   */
  public function adminDashboard() {
    $build = [];

    // Attach Bootstrap CSS
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

    // Hero section
    $build['hero'] = [
      '#markup' => '<div class="container my-5">
                      <div class="p-5 mb-4 bg-light rounded-3 text-center">
                        <h1 class="display-5 fw-bold">Admin Dashboard</h1>
                        <p class="fs-5">Manage movies and bookings here.</p>
                      </div>
                    </div>',
    ];

    // Add New Movie button
    $url = Url::fromRoute('node.add', ['node_type' => 'movie']);
    $link = Link::fromTextAndUrl('Add New Movie', $url)->toRenderable();
    $link['#attributes'] = ['class' => ['btn', 'btn-primary', 'mb-4']];
    $build['add_movie'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container', 'mb-4']],
      'link' => $link,
    ];

    // Latest 5 movies
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'movies')
      ->sort('created', 'DESC')
      ->range(0, 5)
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($nids)) {
      $movies = Node::loadMultiple($nids);
      $cards = [];

      $cards = [];

foreach ($movies as $movie) {
  // Load field values safely
  $title = $movie->getTitle();

  // Poster image
  $poster = '';
  if (!$movie->get('field_poster')->isEmpty()) {
  $file = $movie->get('field_poster')->entity;
  if ($file) {
    $poster_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
    $poster = '<img src="' . $poster_url . '" class="card-img-top" alt="' . $title . '">';
  }
}

  // Genre (taxonomy term reference)
  $genre = '';
  if (!$movie->get('field_genre')->isEmpty()) {
    $term = $movie->get('field_genre')->entity;
    if ($term) {
      $genre = $term->label();
    }
  }

  // Showtime
  $showtime = '';
  if (!$movie->get('field_showtimes')->isEmpty()) {
    $showtime = date('d M Y, h:i A', strtotime($movie->get('field_showtimes')->value));
  }

  // Total seats
  $total_seats = $movie->get('field_total_seats')->value ?? 'N/A';

  // Rating
  $rating = $movie->get('field_rating')->value ?? 'N/A';

  // Build card markup
  $cards[] = [
    '#type' => 'container',
    '#attributes' => ['class' => ['col-md-4', 'mb-4']],
    'card' => [
      '#markup' => '
        <div class="card h-100 shadow-sm">
          ' . $poster . '
          <div class="card-body">
            <h5 class="card-title">' . $title . '</h5>
            <p class="card-text"><strong>Genre:</strong> ' . $genre . '</p>
            <p class="card-text"><strong>Showtime:</strong> ' . $showtime . '</p>
            <p class="card-text"><strong>Total Seats:</strong> ' . $total_seats . '</p>
            <p class="card-text"><strong>Rating:</strong> ' . $rating . '</p>
            <p class="text-muted small">Created: ' . date('d M Y', $movie->getCreatedTime()) . '</p>
            <hr>
            <h6>Seat Map</h6>
            ' . $this->getSeatMapMarkup($movie) . '
          </div>
        </div>
      ',
    ],
  ];
}


      $build['movies'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['container', 'row', 'g-3']],
        'cards' => $cards,
      ];
    }
    else {
      $build['movies'] = [
        '#markup' => '<p class="container">No movies added yet.</p>',
      ];
    }

    return $build;
  }

  /**
   * Customer dashboard page.
   */
  public function customerDashboard() {
    $build = [];

    // Attach Bootstrap CSS
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

    // Hero section
    $build['hero'] = [
      '#markup' => '<div class="container my-5">
                      <div class="p-5 mb-4 bg-light rounded-3 text-center">
                        <h1 class="display-5 fw-bold">Customer Dashboard</h1>
                        <p class="fs-5">Browse movies and make bookings here.</p>
                      </div>
                    </div>',
    ];

    // Latest 5 movies
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'movie')
      ->sort('created', 'DESC')
      ->range(0, 5)
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($nids)) {
      $movies = Node::loadMultiple($nids);
      $cards = [];

      foreach ($movies as $movie) {
        $cards[] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['col-md-4']],
          'card' => [
            '#markup' => '<div class="card h-100 shadow-sm">
                            <div class="card-body">
                              <h5 class="card-title">' . $movie->getTitle() . '</h5>
                              <p class="card-text">Created: ' . date('d M Y', $movie->getCreatedTime()) . '</p>
                            </div>
                          </div>',
          ],
        ];
      }

      $build['movies'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['container', 'row', 'g-3']],
        'cards' => $cards,
      ];
    }
    else {
      $build['movies'] = [
        '#markup' 