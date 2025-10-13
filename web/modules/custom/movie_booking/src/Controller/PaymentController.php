<?php

namespace Drupal\movie_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles dummy payment flow.
 */
class PaymentController extends ControllerBase {

  /**
   * Display the payment page.
   *
   * @param int $booking_id
   *   Booking node ID.
   */
  public function paymentPage($booking_id) {
    $build = [];

    // Attach Bootstrap (optional if your theme already includes it).
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

    // Load the booking node (booking is a node of bundle 'booking').
    $booking = \Drupal::entityTypeManager()->getStorage('node')->load($booking_id);
    if (!$booking || $booking->bundle() !== 'booking') {
      return [
        '#markup' => '<div class="container mt-5 text-center"><h3>Invalid booking ID.</h3></div>',
      ];
    }

    // Get booking details
    $movie_title = '';
    if (!$booking->get('field_movie')->isEmpty() && $booking->get('field_movie')->entity) {
      $movie_title = $booking->get('field_movie')->entity->label();
    }

    $seat_field_value = $booking->get('field_seat_number')->value ?? '';
    $seat_numbers = array_filter(array_map('trim', explode(',', $seat_field_value)));
    $seat_count = count($seat_numbers);

    $price_per_ticket = 200; // Example price
    $total_amount = $seat_count * $price_per_ticket;

    // Confirm URL (use a route that will handle the simulated payment).
    $confirm_url = Url::fromRoute('movie_booking.payment_confirm', ['booking_id' => $booking_id])->toString();

    // Build the markup: display seats and a real submit button.
    $seat_display = $seat_count ? implode(', ', $seat_numbers) : '—';

    $build['#markup'] = '
      <div class="container my-5" style="max-width: 760px;">
        <div class="card shadow p-4">
          <h2 class="mb-4 text-center">💳 Payment Gateway</h2>
          <p><strong>Movie:</strong> ' . htmlspecialchars($movie_title, ENT_QUOTES) . '</p>
          <p><strong>Seats:</strong> ' . $seat_count . ' <span class="text-muted">(' . htmlspecialchars($seat_display, ENT_QUOTES) . ')</span></p>
          <p><strong>Amount:</strong> ₹' . number_format($total_amount) . '</p>

          <hr>
          <h5 class="mt-3">Choose Payment Method</h5>

          <form action="' . $confirm_url . '" method="get" class="mt-3">
            <div class="form-check mt-2">
              <input class="form-check-input" type="radio" name="method" value="card" id="card" checked>
              <label class="form-check-label" for="card">💳 Credit / Debit Card</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="method" value="upi" id="upi">
              <label class="form-check-label" for="upi">📱 UPI (Google Pay / PhonePe / Paytm)</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="method" value="wallet" id="wallet">
              <label class="form-check-label" for="wallet">👛 Wallet</label>
            </div>

            <div class="text-center mt-4">
              <button type="submit" class="btn btn-success btn-lg w-100">
                💰 Make Payment ₹' . number_format($total_amount) . '
              </button>
            </div>
          </form>
        </div>
      </div>
    ';

    return $build;
  }

  /**
   * Handle payment confirmation (simulated payment).
   *
   * Accepts GET (we use GET here so a simple form submit works without CSRF tokens).
   *
   * @param int $booking_id
   *   Booking node ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object (optional).
   */
  public function paymentConfirm($booking_id, Request $request = NULL) {
    $booking = \Drupal::entityTypeManager()->getStorage('node')->load($booking_id);

    if ($booking && $booking->bundle() === 'booking') {
      // Optionally update a payment status field if it exists.
      if ($booking->hasField('field_payment_status')) {
        $booking->set('field_payment_status', 'Paid');
        $booking->save();
      }



      // Add success message that will be visible after redirect.
      \Drupal::messenger()->addStatus($this->t('✅ Payment successful. Your tickets are confirmed.'));
    }
    else {
      \Drupal::messenger()->addError($this->t('Invalid booking. Payment not recorded.'));
    }

    // Redirect to the customer's bookings page.
    $url = Url::fromRoute('movie_booking.customer_bookings');
    return new RedirectResponse($url->toString());
  }

}
