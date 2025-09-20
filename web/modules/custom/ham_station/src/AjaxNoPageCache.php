<?php

namespace Drupal\ham_station;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not cache map AJAX response in the page cache.
 *
 * It is cached in the dynamic cache with a max-age.
 */
class AjaxNoPageCache implements RequestPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if ($request->getPathInfo() === '/ham-map-ajax') {
      return self::DENY;
    }
  }

}
