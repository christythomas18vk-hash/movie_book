<?php

namespace Drupal\movie_booking\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

/**
 * Provides an Add Movie form for Admins.
 */
class MovieAddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'movie_booking_add_movie_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Title
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Movie Title'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['form-control']],
    ];

    // Poster Image
    $form['field_poster'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Movie Poster'),
      '#upload_location' => 'public://movie_posters/',
      '#required' => TRUE,
      '#description' => $this->t('Upload movie poster image (JPG, PNG).'),
    ];

    // Showtime (Datetime)
    $form['field_showtime'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Showtime'),
      '#required' => TRUE,
      '#date_time_element' => 'datetime',
      '#attributes' => ['class' => ['form-control']],
    ];

    // Genre dropdown (taxonomy terms)
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('genre');
    $genre_options = [];
    foreach ($terms as $term) {
      $genre_options[$term->tid] = $term->name;
    }

    $form['field_genre'] = [
      '#type' => 'select',
      '#title' => $this->t('Genre'),
      '#options' => $genre_options,
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select Genre -'),
      '#attributes' => ['class' => ['form-select']],
    ];

    // Total Seats
    $form['field_total_seats'] = [
      '#type' => 'number',
      '#title' => $this->t('Total Seats'),
      '#required' => TRUE,
      '#min' => 1,
      '#attributes' => ['class' => ['form-control']],
      '#ajax' => [
        'callback' => '::generateSeatMap',
        'wrapper' => 'seat-map-json',
        'event' => 'change',
      ],
    ];

    // Seat map (auto-generated JSON)
    $form['field_seats_map'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Seat Map JSON'),
      '#description' => $this->t('Automatically generated based on total seats. You can edit if needed.'),
      '#attributes' => ['class' => ['form-control']],
      '#prefix' => '<div id="seat-map-json">',
      '#suffix' => '</div>',
    ];

    // Rating
    $form['field_rating'] = [
      '#type' => 'number',
      '#title' => $this->t('Rating'),
      '#description' => $this->t('Optional: Decimal rating (0–10).'),
      '#step' => 0.1,
      '#min' => 0,
      '#max' => 10,
      '#attributes' => ['class' => ['form-control']],
    ];

    // Submit button
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Movie'),
      '#button_type' => 'primary',
      '#attributes' => ['class' => ['btn', 'btn-success', 'mt-3']],
    ];

    return $form;
  }

  /**
   * AJAX callback: Auto-generate seat map JSON.
   */
  public function generateSeatMap(array &$form, FormStateInterface $form_state) {
    $total_seats = $form_state->getValue('field_total_seats');
    if ($total_seats && $total_seats > 0) {
      $form['field_seats_map']['#value'] = $this->createSeatMapJSON($total_seats);
    }
    return $form['field_seats_map'];
  }

  /**
   * Helper function to generate seat map JSON based on total seats.
   */
  private function createSeatMapJSON($total) {
    $seat_map = [];
    $alphabet = range('A', 'Z');
    $seat_number = 1;
    $seats_per_row = 8; // Fixed seats per row

    $row_index = 0;

    while ($seat_number <= $total) {
        $row_letter = $alphabet[$row_index];
        $row = [];

        for ($j = 1; $j <= $seats_per_row; $j++) {
            if ($seat_number > $total) {
                break;
            }
            $row[] = [
                'label' => $row_letter . $j,
                'status' => 'available', // default status
            ];
            $seat_number++;
        }

        $seat_map[$row_letter] = $row;
        $row_index++;
    }

    return json_encode($seat_map, JSON_PRETTY_PRINT);
}



  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle uploaded poster file
    $poster_fid = $form_state->getValue('field_poster');
    $poster_target = [];

    if (!empty($poster_fid)) {
      $file = File::load(reset($poster_fid));
      $file->setPermanent();
      $file->save();
      $poster_target = [['target_id' => $file->id()]];
    }

    // Prepare showtime (convert to proper format)
    $showtime = $form_state->getValue('field_showtime');
    $showtime_value = '';
    if ($showtime instanceof \Drupal\Core\Datetime\DrupalDateTime) {
      $showtime_value = $showtime->format('Y-m-d\TH:i:s');
    }

    // Auto-generate seat map if empty
    $seat_map_json = $form_state->getValue('field_seats_map');
    if (empty(trim($seat_map_json))) {
      $seat_map_json = $this->createSeatMapJSON($form_state->getValue('field_total_seats'));
    }

    // Create Movie Node
    $node = Node::create([
      'type' => 'movies',
      'title' => $form_state->getValue('title'),
      'field_poster' => $poster_target,
      'field_showtime' => $showtime_value,
      'field_genre' => [['target_id' => $form_state->getValue('field_genre')]],
      'field_total_seats' => $form_state->getValue('field_total_seats'),
      'field_seat_map' => $seat_map_json,
      'field_rating' => $form_state->getValue('field_rating'),
      'status' => 1,
    ]);

    $node->save();

    $this->messenger()->addStatus($this->t('Movie "@title" has been added successfully with seat map!', [
      '@title' => $node->getTitle(),
    ]));

    // Redirect to Admin Dashboard
    $form_state->setRedirect('movie_booking.admin_dashboard');
  }

}
