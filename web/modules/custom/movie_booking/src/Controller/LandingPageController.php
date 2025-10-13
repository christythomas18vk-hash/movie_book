<?php

namespace Drupal\movie_booking\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides the landing page for the Movie Booking system.
 */
class LandingPageController extends ControllerBase {

  /**
   * Renders the landing page.
   */
  public function landingPage() {
    $build = [];

    // ✅ Attach Bootstrap (from CDN)
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

    // ✅ Page markup
    $build['#markup'] = '
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

      <main class="text-center my-5">
        <div class="container">
          <h1 class="display-4 fw-bold mb-3">Welcome to Movie Booking System</h1>
          <p class="lead mb-4">Book your favorite movie tickets anytime, anywhere.</p>
          <div class="d-flex justify-content-center gap-3">
            <a href="/admin/login" class="btn btn-primary btn-lg">Admin Login</a>
            <a href="/customer/login" class="btn btn-success btn-lg">Customer Login</a>
          </div>
        </div>
      </main>

      <footer class="bg-light text-center py-3 mt-5 border-top">
        <p class="mb-0">© ' . date('Y') . ' Movie Booking. All rights reserved.</p>
      </footer>
    ';

    return $build;
  }

}
