<?php

namespace Drupal\movie_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Messenger\MessengerInterface;

class MovieManageController extends ControllerBase {

  /**
   * Edit a movie node.
   */
  public function editMovie(Node $node) {
    if ($node->bundle() !== 'movies') {
      $this->messenger()->addError('Invalid movie.');
      return new RedirectResponse(Url::fromRoute('movie_booking.admin_dashboard')->toString());
    }

    // Redirect to the default Drupal node edit form.
    return new RedirectResponse('/node/' . $node->id() . '/edit');
  }

  /**
   * Delete a movie node.
   */
  public function deleteMovie(Node $node) {
    if ($node->bundle() !== 'movies') {
      $this->messenger()->addError('Invalid movie.');
      return new RedirectResponse(Url::fromRoute('movie_booking.admin_dashboard')->toString());
    }

    $title = $node->getTitle();
    $node->delete();
    $this->messenger()->addStatus("Movie '$title' has been deleted.");

    return new RedirectResponse(Url::fromRoute('movie_booking.admin_dashboard')->toString());
  }
}
