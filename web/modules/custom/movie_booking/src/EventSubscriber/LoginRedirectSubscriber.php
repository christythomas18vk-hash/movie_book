<?php

namespace Drupal\movie_booking\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\user\Event\UserLoginRedirectEvent;

class LoginRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Subscribe to the user login redirect event.
   */
  public static function getSubscribedEvents() {
    // High priority so it runs before default redirects
    return [
      UserLoginRedirectEvent::class => ['onUserLoginRedirect', 100],
    ];
  }

  /**
   * Redirect users after login based on role.
   */
  public function onUserLoginRedirect(UserLoginRedirectEvent $event) {
    $account = $event->getAccount();

    // Determine destination route
    if (in_array('administrator', $account->getRoles())) {
      $route = 'movie_booking.admin_dashboard';
    } else {
      $route = 'movie_booking.customer_dashboard';
    }

    $url = Url::fromRoute($route)->toString();
    $event->setResponse(new TrustedRedirectResponse($url));
  }
}
