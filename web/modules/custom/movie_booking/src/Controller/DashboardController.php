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
    '#markup' => '
      <div class="container my-5">
        <div class="p-5 mb-4 bg-light rounded-3 text-center">
          <h1 class="display-5 fw-bold">Admin Dashboard</h1>
          <p class="fs-5">Manage movies and bookings here.</p>
        </div>
      </div>',
  ];

  // Add New Movie button
  $url = Url::fromUri('internal:/admin/movie/add');
  $link = Link::fromTextAndUrl('Add New Movie', $url)->toRenderable();
  $link['#attributes'] = ['class' => ['btn', 'btn-primary', 'mb-4']];
  $build['add_movie'] = [
    '#type' => 'container',
    '#attributes' => ['class' => ['container', 'mb-4']],
    'link' => $link,
  ];

  // Latest 6 movies
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'movies')
    ->sort('created', 'DESC')
    ->range(0, 6)
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

      $build['#attached']['html_head'][] = [
        [
          '#tag' => 'style',
          '#value' => '
            .seat-map-container { overflow-x: auto; padding: 5px; }
            .seat-map .seat-row { flex-wrap: wrap; }
            .seat-map button.btn { min-width: 40px; margin: 2px; }
          ',
        ],
        'custom_seat_map_styles',
      ];

      // Generate Edit and Delete URLs
      $edit_url = Url::fromRoute('movie_booking.edit_movie', ['node' => $movie->id()])->toString();
      $delete_url = Url::fromRoute('movie_booking.delete_movie', ['node' => $movie->id()])->toString();

      // Build card markup
      $cards[] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-md-4', 'mb-4']],
        'card' => [
          '#type' => 'inline_template',
          '#template' => '
            <div class="card h-100 shadow-sm">
              {{ poster|raw }}
              <div class="card-body">
                <h5 class="card-title"> {{ title }} </h5>
                <p class="card-text"><strong>Genre:</strong> {{ genre }}</p>
                <p class="card-text"><strong>Showtime:</strong> {{ showtime }} </p>
                <p class="card-text"><strong>Total Seats:</strong> {{ total_seats }}</p>
                <p class="card-text"><strong>Rating:</strong> {{ rating }}</p>
                <p class="text-muted small">Created: {{ created }}</p>
                <hr>
                <div class="d-flex justify-content-between">
                  <a href="{{ edit_url }}" class="btn btn-warning btn-sm">Edit</a>
                  <a href="{{ delete_url }}" class="btn btn-danger btn-sm">Delete</a>
                </div>
                <h6>Seat Map</h6>
                {{ seat_map_markup|raw }}
              </div>
            </div>
          ',
          '#context' => [
          'poster' => $poster, // already safe HTML
            'title' => $title,
            'genre' => $genre,
            'showtime' => $showtime,
            'total_seats' => $total_seats,
            'rating' => $rating,
            'created' => date('d M Y', $movie->getCreatedTime()),
            'seat_map_markup' => $this->getSeatMapMarkup($movie), // render array
            'edit_url' => $edit_url,
            'delete_url' => $delete_url,
          ],
          '#cache' => [
          'max-age' => 0, // disable caching for this card
        ]
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
                        <p class="fs-5">Manage bookings here.</p>
                      </div>
                    </div>
                    <div class="mb-4">
                        <a href="/customer/bookings" class="btn btn-primary">My Bookings</a>
                    </div>'
                    ,
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

      $build['#attached']['html_head'][] = [
        [
          '#tag' => 'style',
          '#value' => '
            .seat-map-container { overflow-x: auto; padding: 5px; }
            .seat-map .seat-row { flex-wrap: wrap; }
            .seat-map button.btn { min-width: 40px; margin: 2px; }
          ',
        ],
        'custom_seat_map_styles',
      ];


      // Build card markup
      $cards[] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-md-6', 'mb-4']],
        'card' => [
          '#type' => 'inline_template',
          '#template' => '
            <div class="card h-100 shadow-sm">
              {{ poster|raw }}
              <div class="card-body">
                <h5 class="card-title"> {{ title }} </h5>
                <p class="card-text"><strong>Genre:</strong> {{ genre }}</p>
                <p class="card-text"><strong>Showtime:</strong> {{ showtime }} </p>
                <p class="card-text"><strong>Total Seats:</strong> {{ total_seats }}</p>
                <p class="card-text"><strong>Rating:</strong> {{ rating }}</p>
                <p class="text-muted small">Created: {{ created }}</p>
                <hr>
                <h6>Seat Map</h6>
                {{ seat_map_markup|raw }}
                 <hr>
                <a href="{{ booking_url }}" class="btn btn-primary">Book Now</a>
              </div>
            </div>
          ',
          '#context' => [
          'poster' => $poster, // already safe HTML
          'title' => $title,
          'genre' => $genre,
          'showtime' => $showtime,
          'total_seats' => $total_seats,
          'rating' => $rating,
          'created' => date('d M Y', $movie->getCreatedTime()),
          'seat_map_markup' => $this->getSeatMapMarkup($movie), // render array
          'booking_url' => \Drupal\Core\Url::fromRoute('movie_booking.booking_form', ['node' => $movie->id()])->toString(),
        ],
        '#cache' => [
          'max-age' => 0, // disable caching for this card
        ]
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
 * Get seat map markup for a movie node.
 */
private function getSeatMapMarkup(Node $movie) {
  // Instantiate SeatController.
  $seat_controller = new \Drupal\movie_booking\Controller\SeatController();

  // Call seatMapAjax() method.
  $response = $seat_controller->seatMapAjax($movie);

  // Decode JSON to get the markup.
  $data = json_decode($response->getContent(), TRUE);

  // Return as render array so Drupal treats it as HTML.
  return [
    '#type' => 'inline_template',
    '#template' => '{{ markup|raw }}',
    '#context' => [
      'markup' => $data['markup'] ?? '<p>No seat map available.</p>',
    ],
  ];
}


}


