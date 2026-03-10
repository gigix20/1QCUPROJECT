<?php
// backend/services/QRService.php

class QRService {

  // GENERATE QR CODE STRING
  public static function generate($asset_id) {
    $date      = date('Ymd');
    $random    = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4));
    return 'ONEQCU-' . $date . '-' . $random;
  }

}
?>