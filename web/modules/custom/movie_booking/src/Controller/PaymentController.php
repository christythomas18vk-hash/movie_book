<?php

namespace Drupal\movie_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;
use Drupal\Core\Render\Markup;


/**
 * Handles dummy payment flow.
 */
class PaymentController extends ControllerBase {

  /**
   * Display the payment page.
   */
  public function paymentPage($booking_id) {
    $build = [];

    // Load booking node.
    $booking = \Drupal::entityTypeManager()->getStorage('node')->load($booking_id);
    if (!$booking || $booking->bundle() !== 'booking') {
      return [
        '#markup' => '<div class="container mt-5 text-center"><h3>Invalid booking ID.</h3></div>',
      ];
    }

    // Get linked movie.
    $movie = $booking->get('field_movie')->entity ?? NULL;
    if (!$movie) {
      return [
        '#markup' => '<div class="container mt-5 text-center"><h3>Booking not linked to any movie.</h3></div>',
      ];
    }

    // Booking + movie details.
    $movie_title = $movie->label();
    $ticket_price = $movie->get('field_ticket_price')->value ?? 200; // fallback
    $seat_field_value = $booking->get('field_seat_number')->value ?? '';
    $seat_numbers = array_filter(array_map('trim', explode(',', $seat_field_value)));
    $seat_count = count($seat_numbers);
    $seat_display = $seat_count ? implode(', ', $seat_numbers) : '—';

    // Calculate total dynamically.
    $total_amount = $seat_count * $ticket_price;

    // URL to payment confirmation.
    $confirm_url = Url::fromRoute('movie_booking.payment_confirm', ['booking_id' => $booking_id])->toString();

    // Attach Bootstrap + minimal styles.
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

    $build['#attached']['html_head'][] = [
      [
        '#tag' => 'style',
        '#value' => '
          .payment-card {
            max-width: 750px;
            margin: 40px auto;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
          }
          .seat-badge {
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            padding: 4px 8px;
            margin: 2px;
            display: inline-block;
            font-size: 0.9rem;
          }
        ',
      ],
      'payment_custom_styles',
    ];

    // Build markup.
    $markup = '
      <div class="container">
        <div class="card payment-card p-4">
          <h2 class="text-center mb-4">💳 Payment Gateway</h2>
          <p><strong>Movie:</strong> ' . htmlspecialchars($movie_title) . '</p>
          <p><strong>Ticket Price:</strong> ₹' . number_format($ticket_price) . '</p>
          <p><strong>Seats Selected:</strong> ' . $seat_count . ' (' . htmlspecialchars($seat_display) . ')</p>
          <p><strong>Total Amount:</strong> ₹' . number_format($total_amount) . '</p>
          <hr>

          <h5 class="mt-3 mb-3">Choose Payment Method</h5>
          <form action="' . $confirm_url . '" method="get">
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="method" value="Debit Card" id="debit" checked>
              <label class="form-check-label" for="debit">💳 Debit Card</label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="method" value="Credit Card" id="credit">
              <label class="form-check-label" for="credit">💳 Credit Card</label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="method" value="Net Banking" id="netbank">
              <label class="form-check-label" for="netbank">🏦 Net Banking</label>
            </div>
            <div class="form-check mb-4">
              <input class="form-check-input" type="radio" name="method" value="UPI" id="upi">
              <label class="form-check-label" for="upi">📱 UPI</label>
            </div>

            <div class="text-center">
              <button type="submit" class="btn btn-success btn-lg w-100" onclick="this.disabled=true; this.form.submit();">
                💰 Make Payment ₹<?php print number_format($total_amount); ?>
              </button>
            </div>
          </form>
        </div>
      </div>
    ';

    $build['#markup'] = Markup::create($markup);
    $build['#cache']['max-age'] = 0;

    return $build;
  }

  /**
   * Handle payment confirmation (simulated payment).
   */
  public function paymentConfirm($booking_id, Request $request = NULL) {
    $booking = \Drupal::entityTypeManager()->getStorage('node')->load($booking_id);

    if ($booking && $booking->bundle() === 'booking') {
      $method = $request->query->get('method', 'UPI');
      $movie = $booking->get('field_movie')->entity ?? NULL;

      // Fetch dynamic ticket price.
      $ticket_price = $movie ? ($movie->get('field_ticket_price')->value ?? 200) : 200;

      $seat_field_value = $booking->get('field_seat_number')->value ?? '';
      $seat_numbers = array_filter(array_map('trim', explode(',', $seat_field_value)));
      $seat_count = count($seat_numbers);
      $total_amount = $seat_count * $ticket_price;

      $guid = $this->generateGuid();

      // Create a Transaction node (if content type exists).
      $transaction = Node::create([
        'type' => 'transaction',
        'title' => 'Transaction ' . $guid,
        'field_transaction_amount' => $total_amount,
        'field_transaction_date' => date('Y-m-d H:i:s'),
        'field_transaction_id' => $guid,
        'field_transaction_method' => $this->getTaxonomyTermId($method, 'transaction_method'),
        'field_booking_reference' => $booking_id,
      ]);
      $transaction->save();

      if ($booking->hasField('field_payment_status')) {
        $booking->set('field_payment_status', 'Paid');
        $booking->save();
      }

      \Drupal::messenger()->addStatus($this->t('✅ Payment successful! Transaction ID: @tid', ['@tid' => $guid]));
      return new RedirectResponse(Url::fromRoute('movie_booking.customer_bookings')->toString());
    }

    \Drupal::messenger()->addError($this->t('❌ Invalid booking. Payment failed.'));
    return new RedirectResponse(Url::fromRoute('movie_booking.customer_bookings')->toString());
  }

  /**
   * Generate a unique transaction ID (GUID).
   */
  private function generateGuid() {
    return sprintf(
      '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
      mt_rand(0, 65535),
      mt_rand(0, 65535),
      mt_rand(0, 65535),
      mt_rand(16384, 20479),
      mt_rand(32768, 49151),
      mt_rand(0, 65535),
      mt_rand(0, 65535),
      mt_rand(0, 65535)
    );
  }

  /**
   * Get taxonomy term ID by name (creates if not exists).
   */
  private function getTaxonomyTermId($term_name, $vocabulary) {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => $term_name, 'vid' => $vocabulary]);

    if (!empty($terms)) {
      return reset($terms)->id();
    }

    $term = \Drupal\taxonomy\Entity\Term::create([
      'vid' => $vocabulary,
      'name' => $term_name,
    ]);
    $term->save();

    return $term->id();
  }

}
