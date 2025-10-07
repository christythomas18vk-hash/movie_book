<?php

namespace Drupal\movie_booking\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Booking form for movies.
 */
class BookingForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'movie_booking_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Node $node = NULL) {
    if (!$node) {
      return ['message' => ['#markup' => '<p>Movie not found.</p>']];
    }

    // Logged-in user
    $current_user = \Drupal::currentUser();
    $user = User::load($current_user->id());
    $customer_name = $user ? $user->getDisplayName() : '';

    // Movie title
    $form['movie_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Movie Title'),
      '#default_value' => $node->getTitle(),
      '#disabled' => TRUE,
    ];

    // Showtime
    $form['showtime'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Showtime'),
      '#default_value' => $node->get('field_showtimes')->value ?? '',
      '#disabled' => TRUE,
    ];

    // Customer name
    $form['customer_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer Name'),
      '#default_value' => $customer_name,
      '#disabled' => TRUE,
    ];

    // Seat map container
    $form['seat_map'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['seat-map-container']],
      '#tree' => TRUE,
    ];

    if ($node->hasField('field_seat_map') && !$node->get('field_seat_map')->isEmpty()) {
      $seat_map_json = $node->get('field_seat_map')->value;
      $seat_map = json_decode($seat_map_json, TRUE);

      if (is_array($seat_map)) {
        foreach ($seat_map as $row_label => $seats) {
          $form['seat_map'][$row_label] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['seat-row', 'mb-2']],
          ];

          foreach ($seats as $seat) {
            if (!isset($seat['label'], $seat['status'])) continue;

            $disabled = $seat['status'] === 'sold';
            $class = $disabled ? 'seat sold' : 'seat available';

            $form['seat_map'][$row_label][$seat['label']] = [
              '#type' => 'checkbox',
              '#title' => $seat['label'],
              '#title_display' => 'before',
              '#disabled' => $disabled,
              '#attributes' => ['class' => [$class]],
            ];
          }
        }
      }
    }

    // Submit button
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Book Now'),
      '#attributes' => ['class' => ['btn', 'btn-primary', 'mt-3']],
    ];

    // Attach custom CSS for seat map styling
    $form['#attached']['html_head'][] = [
      [
        '#tag' => 'style',
        '#value' => '
          .seat-map-container { overflow-x: auto; padding: 10px; text-align: center; }
          .seat-row { display: flex; justify-content: center; gap: 8px; margin-bottom: 8px; }
          .seat-row .form-type-checkbox { display: inline-block; }
          .seat input[type="checkbox"] { display: none; }
          .seat label { 
            display: inline-block; 
            width: 40px; height: 40px; 
            border-radius: 5px; 
            text-align: center; 
            line-height: 40px; 
            font-weight: bold; 
            color: #fff;
            cursor: pointer;
          }
          .seat.available label { background-color: #28a745; }
          .seat.sold label { background-color: #dc3545; cursor: not-allowed; opacity: 0.6; }
          .seat input[type="checkbox"]:checked + label { background-color: #007bff; } /* Blue tick */
        ',
      ],
      'seat_map_styles',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
 public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_seats = [];

    // Flatten seat_map checkboxes properly
    $seat_map_values = $form_state->getValue('seat_map') ?? [];
    foreach ($seat_map_values as $row) {
        if (is_array($row)) {
            foreach ($row as $seat_label => $checked) {
                if ($checked) {
                    $selected_seats[] = $seat_label;
                }
            }
        }
    }

    if (empty($selected_seats)) {
        \Drupal::messenger()->addError($this->t('Please select at least one seat.'));
        return;
    }

    // Get the node from the form state (passed in buildForm)
    $node = $form_state->getBuildInfo()['args'][0] ?? NULL;
    if (!$node instanceof Node) {
        \Drupal::messenger()->addError($this->t('Movie not found.'));
        return;
    }

    // Current user
    $current_user = \Drupal::currentUser();
    $user = User::load($current_user->id());

    // Create Booking node
    $booking = Node::create([
        'type' => 'booking',
        'title' => 'Booking for ' . $node->getTitle() . ' (' . implode(', ', $selected_seats) . ')',
        'field_customer' => $user->id(),
        'field_movie' => $node->id(),
        'field_seat_number' => implode(', ', $selected_seats),
        'field_showtime' => $node->get('field_showtimes')->value ?? '',
    ]);
    $booking->save();

    // Update movie seat map
    $this->updateSeatMap($node, $selected_seats);

    \Drupal::messenger()->addStatus($this->t('Booking confirmed for seats: @seats', [
        '@seats' => implode(', ', $selected_seats),
    ]));

    $form_state->setRedirectUrl(\Drupal\Core\Url::fromRoute('movie_booking.customer_dashboard'));
}

  /**
   * Updates the movie node's seat map, marking selected seats as sold.
   */
  private function updateSeatMap(Node $node, array $selected_seats) {
    if (!$node->hasField('field_seat_map') || $node->get('field_seat_map')->isEmpty()) {
      return;
    }

    $seat_map_json = $node->get('field_seat_map')->value;
    $seat_map = json_decode($seat_map_json, TRUE);

    if (!is_array($seat_map)) {
      return;
    }

    foreach ($seat_map as $row_label => &$row) {
      foreach ($row as &$seat) {
        if (in_array($seat['label'], $selected_seats)) {
          $seat['status'] = 'sold';
        }
      }
    }

    // Save updated map
    $node->set('field_seat_map', json_encode($seat_map, JSON_PRETTY_PRINT));
    $node->save();
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
  }

}

