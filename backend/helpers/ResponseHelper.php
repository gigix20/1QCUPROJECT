<?php

class ResponseHelper {

  // SUCCESS RESPONSE
  public static function sendSuccess($data = null, $message = 'Success.') {
    header('Content-Type: application/json');
    echo json_encode([
      'status'  => 'success',
      'message' => $message,
      'data'    => $data
    ]);
    exit;
  }

  // ERROR RESPONSE
  public static function sendError($code, $message) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
      'status'  => 'error',
      'message' => $message,
      'data'    => null
    ]);
    exit;
  }
}
?>