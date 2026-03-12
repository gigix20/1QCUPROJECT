<?php
// backend/services/AssetIdService.php

class AssetIdService {
  // GENERATE ASSET ID
  public static function generate($dept_name, $item_type_code, $seq) {
    $dept_code = strtoupper(explode(' ', trim($dept_name))[0]);
    $item_num  = str_pad($seq, 4, '0', STR_PAD_LEFT);
    return 'AST-' . $dept_code . '-' . $item_type_code . '-' . $item_num;
  }

  // GENERATE QR CODE VALUE
  // QR contains a URL that points to the asset
  public static function generateQR($asset_id) {
    $base_url = 'http://localhost/1QCUPROJECT/views/staff/assets.php';
    return $base_url . '?asset_id=' . urlencode($asset_id);
  }
}
?>