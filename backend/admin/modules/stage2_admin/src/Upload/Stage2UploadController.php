<?php

namespace Drupal\stage2_admin\Upload;

use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\stage2_admin\Upload\UploadHandler;


/**
 * Controller routines for GiServices routes.
 */

class Stage2UploadController extends ControllerBase {

  public static function init (Request $request ) {

    $upload_options = array('print_response'->false);
    $upload_handler = new UploadHandler($upload_options, true);

  return new JsonResponse($upload_handler);
  }

}
