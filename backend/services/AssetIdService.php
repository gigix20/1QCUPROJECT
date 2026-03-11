<?php
// backend/services/AssetIdService.php

class AssetIdService {
  // GENERATE ASSET ID
  public static function generate($dept_name, $item_type_code, $seq) {
    // Get first word of dept name e.g. "IT Department" -> "IT"
    $dept_code = strtoupper(explode(' ', trim($dept_name))[0]);
    $item_num  = str_pad($seq, 4, '0', STR_PAD_LEFT);
    return 'AST-' . $dept_code . '-' . $item_type_code . '-' . $item_num;
  }

  // GENERATE QR CODE VALUE
  public static function generateQR() {
    $rand = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4));
    return 'ONEQCU-' . date('Ymd') . '-' . $rand;
  }
}
?>