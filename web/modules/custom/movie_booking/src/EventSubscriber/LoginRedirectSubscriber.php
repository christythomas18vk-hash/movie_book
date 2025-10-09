<?php

namespace Drupal\movie_booking\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\user\UserInterface;

class LoginRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Subscribe to the user login redirect event.
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest', 100],
    ];
  }

  /**
   * Redirect users after login based on role.
   */
  public function onKernelRequest(KernelEvent $event) {
    $request = $event->getRequest();
    
    // Only handle user login page
    if ($request->getPathInfo() !== '/user/login') {
      return;
    }
    
    // Check if user is already logged in
    $user = \Drupal::currentUser();
    if (!$user->isAuthenticated()) {
      return;
    }
    
    // Determine destination route
    if (in_array('administrator', $user->getRoles())) {
      $route = 'movie_booking.admin_dashboard';
    } else {
      $route = 'movie_booking.customer_dashboard';
    }

    $url = Url::fromRoute($route)->toString();
    $response = new TrustedRedirectResponse($url);
    $event->setResponse($response);
  }
}