<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class ReportService
{

  private static function makePDF($html, $filename)
  {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled',      true);
    $options->set('defaultFont',          'DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream($filename . '.pdf', ['Attachment' => true]);
    exit;
  }

  private static function buildHeader($title, $subtitle = '')
  {
    $date = date('F d, Y h:i A');
    return '
      <div style="
        background: linear-gradient(135deg, #2d1b47 0%, #7c3aed 100%);
        padding: 24px 32px;
        margin-bottom: 24px;
        border-radius: 8px;
      ">
        <div style="color:#fff; font-size:22px; font-weight:700; letter-spacing:1px;">
          ONEQCU Asset Management System
        </div>
        <div style="color:#e9d5ff; font-size:15px; margin-top:4px; font-weight:600;">
          ' . htmlspecialchars($title) . '
        </div>
        ' . ($subtitle ? '<div style="color:#c4b5fd;font-size:12px;margin-top:2px;">' . htmlspecialchars($subtitle) . '</div>' : '') . '
        <div style="color:#c4b5fd; font-size:11px; margin-top:8px;">
          Generated: ' . $date . '
        </div>
      </div>
    ';
  }

  private static function buildFooter()
  {
    return '
      <div style="
        margin-top: 32px;
        padding-top: 12px;
        border-top: 2px solid #7c3aed;
        text-align: center;
        color: #888;
        font-size: 10px;
      ">
        ONEQCU Asset Management System &mdash; Confidential &mdash; Generated ' . date('Y') . '
      </div>
    ';
  }

  private static function buildSummaryBoxes($boxes)
  {
    $html = '<div style="display:flex; gap:12px; margin-bottom:24px;">';
    foreach ($boxes as $box) {
      $html .= '
        <div style="
          flex:1;
          background:' . $box['bg'] . ';
          border-left: 4px solid ' . $box['color'] . ';
          padding: 14px 16px;
          border-radius: 6px;
        ">
          <div style="font-size:11px; color:#666; font-weight:600; text-transform:uppercase;">
            ' . $box['label'] . '
          </div>
          <div style="font-size:26px; font-weight:700; color:' . $box['color'] . '; margin-top:4px;">
            ' . $box['value'] . '
          </div>
        </div>
      ';
    }
    $html .= '</div>';
    return $html;
  }

  private static function buildPageHTML($body)
  {
    return '
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <style>
          * { margin:0; padding:0; box-sizing:border-box; }
          body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1a1a2e;
            padding: 24px;
            background: #ffffff;
          }
          table { border-collapse: collapse; width: 100%; }
          th, td { text-align: left; }
        </style>
      </head>
      <body>' . $body . '</body>
      </html>
    ';
  }

  private static function gdToBase64($img)
  {
    ob_start();
    imagepng($img);
    $data = ob_get_clean();
    imagedestroy($img);
    return 'data:image/png;base64,' . base64_encode($data);
  }

  private static function hex2gd($img, $hex)
  {
    $hex = ltrim($hex, '#');
    return imagecolorallocate(
      $img,
      hexdec(substr($hex, 0, 2)),
      hexdec(substr($hex, 2, 2)),
      hexdec(substr($hex, 4, 2))
    );
  }

  private static function buildPieChart($slices, $title)
  {
    $W = 550;
    $H = 380;
    $img = imagecreatetruecolor($W, $H);
    imagesavealpha($img, true);

    $white = imagecolorallocate($img, 255, 255, 255);
    $dark  = imagecolorallocate($img, 45,  27,  71);
    $grey  = imagecolorallocate($img, 100, 100, 100);
    imagefilledrectangle($img, 0, 0, $W, $H, $white);

    imagestring($img, 5, 10, 10, $title, $dark);

    $slices = array_filter($slices, fn ($s) => $s['value'] > 0);
    if (empty($slices)) {
      imagestring($img, 3, 200, 150, 'No data', $grey);
      return self::gdToBase64($img);
    }

    $total      = array_sum(array_column($slices, 'value'));
    $cx         = 200;
    $cy = 160;
    $r = 120;
    $startAngle = 0;

    foreach ($slices as $slice) {
      $sweep = ($slice['value'] / $total) * 360;
      $color = self::hex2gd($img, $slice['color']);
      imagefilledarc(
        $img,
        $cx,
        $cy,
        $r * 2,
        $r * 2,
        (int)$startAngle,
        (int)($startAngle + $sweep),
        $color,
        IMG_ARC_PIE
      );

      $midAngle = deg2rad($startAngle + $sweep / 2);
      $lx  = (int)($cx + cos($midAngle) * $r * 0.6);
      $ly  = (int)($cy + sin($midAngle) * $r * 0.6);
      $pct = round($slice['value'] / $total * 100, 1) . '%';
      imagestring($img, 5, $lx - 10, $ly - 6, $pct, $white);

      $startAngle += $sweep;
    }

    $border = imagecolorallocate($img, 255, 255, 255);
    imagearc($img, $cx, $cy, $r * 2, $r * 2, 0, 360, $border);

    $lx = 360;
    $ly = 80;
    foreach ($slices as $slice) {
      $color = self::hex2gd($img, $slice['color']);
      imagefilledrectangle($img, $lx, $ly, $lx + 14, $ly + 14, $color);
      imagerectangle($img, $lx, $ly, $lx + 14, $ly + 14, $dark);
      $pct = round($slice['value'] / $total * 100, 1);
      imagestring($img, 3, $lx + 20, $ly + 2, $slice['label'] . ' (' . $pct . '%)', $dark);
      $ly += 32;
    }

    return self::gdToBase64($img);
  }

  private static function buildBarChart($groups, $legendLabels, $title)
  {
    $groupCount  = count($groups);
    $seriesCount = empty($groups) ? 1 : count($groups[0]['values']);

    $barW      = 18;
    $groupGap  = 20;
    $leftPad   = 55;
    $rightPad  = 20;
    $topPad    = 50;
    $bottomPad = 60;

    $chartW = $leftPad + ($groupCount * ($seriesCount * $barW + $groupGap)) + $rightPad;
    $chartW = max($chartW, 500);
    $chartH = 320;

    $img   = imagecreatetruecolor($chartW, $chartH);
    $white = imagecolorallocate($img, 255, 255, 255);
    $dark  = imagecolorallocate($img, 45,  27,  71);
    $lgrey = imagecolorallocate($img, 220, 220, 220);
    $mgrey = imagecolorallocate($img, 150, 150, 150);
    imagefilledrectangle($img, 0, 0, $chartW, $chartH, $white);

    imagestring($img, 4, 10, 10, $title, $dark);

    $maxVal = 1;
    foreach ($groups as $g) {
      foreach ($g['values'] as $v) {
        if ($v > $maxVal) $maxVal = $v;
      }
    }

    $chartAreaH = $chartH - $topPad - $bottomPad;

    for ($i = 0; $i <= 5; $i++) {
      $y   = $topPad + $chartAreaH - (int)($chartAreaH * $i / 5);
      $val = (int)round($maxVal * $i / 5);
      imageline($img, $leftPad, $y, $chartW - $rightPad, $y, $lgrey);
      imagestring($img, 1, 2, $y - 6, $val, $mgrey);
    }

    $x = $leftPad + (int)($groupGap / 2);
    foreach ($groups as $g) {
      $bx = $x;
      foreach ($g['values'] as $si => $val) {
        $barH  = $val > 0 ? (int)($chartAreaH * $val / $maxVal) : 0;
        $left  = $bx;
        $right = $bx + $barW - 2;
        $top   = $topPad + $chartAreaH - $barH;
        $bot   = $topPad + $chartAreaH;

        $color = self::hex2gd($img, $g['colors'][$si] ?? '#7c3aed');
        imagefilledrectangle($img, $left, $top, $right, $bot, $color);

        if ($val > 0) {
          imagestring($img, 1, $left + 2, $top - 12, (string)$val, $dark);
        }
        $bx += $barW;
      }

      $labelX = $x + (int)(($seriesCount * $barW) / 2) - (int)(strlen($g['label']) * 3);
      imagestring($img, 1, $labelX, $topPad + $chartAreaH + 5, $g['label'], $dark);

      $x += $seriesCount * $barW + $groupGap;
    }

    imageline($img, $leftPad, $topPad, $leftPad, $topPad + $chartAreaH, $mgrey);
    imageline($img, $leftPad, $topPad + $chartAreaH, $chartW - $rightPad, $topPad + $chartAreaH, $mgrey);

    if (!empty($legendLabels) && !empty($groups)) {
      $lx = $leftPad;
      $ly = $chartH - 20;
      foreach ($legendLabels as $i => $label) {
        $color = self::hex2gd($img, $groups[$i]['colors'][0] ?? '#7c3aed');
        imagefilledrectangle($img, $lx, $ly, $lx + 10, $ly + 10, $color);
        imagestring($img, 1, $lx + 14, $ly + 1, $label, $dark);
        $lx += strlen($label) * 7 + 24;
      }
    }

    return self::gdToBase64($img);
  }

  public static function exportAssetComplete($data)
  {
    $rows       = $data['rows'];
    $byCategory = $data['by_category'] ?? [];
    $byItemType = $data['by_item_type'] ?? [];
    $total      = 0;

    $statusColors = [
      'Available'   => '#15803d',
      'In Use'      => '#1d4ed8',
      'Maintenance' => '#b45309',
    ];

    $statusSlices = [];
    foreach ($rows as $row) {
      $total          += (int)$row['TOTAL'];
      $statusSlices[]  = [
        'label' => $row['STATUS'],
        'value' => (int)$row['TOTAL'],
        'color' => $statusColors[$row['STATUS']] ?? '#7c3aed',
      ];
    }

    $categoryColors = ['#7c3aed', '#1d4ed8', '#15803d', '#b45309', '#be185d', '#0369a1', '#c2410c'];
    $categorySlices = [];
    foreach ($byCategory as $i => $row) {
      $categorySlices[] = [
        'label' => $row['LABEL'] ?? '—',
        'value' => (int)$row['TOTAL'],
        'color' => $categoryColors[$i % count($categoryColors)],
      ];
    }

    $typeColors = ['#065f46', '#1d4ed8', '#b45309', '#7c3aed', '#dc2626', '#0f766e', '#374151'];
    $typeSlices = [];
    foreach ($byItemType as $i => $row) {
      $typeSlices[] = [
        'label' => $row['LABEL'] ?? '—',
        'value' => (int)$row['TOTAL'],
        'color' => $typeColors[$i % count($typeColors)],
      ];
    }

    $statusChartImg   = self::buildPieChart($statusSlices,   'By Status');
    $categoryChartImg = self::buildPieChart($categorySlices, 'By Category');
    $typeChartImg     = self::buildPieChart($typeSlices,     'By Item Type');

    $boxes = [];
    foreach ($rows as $row) {
      $color   = $statusColors[$row['STATUS']] ?? '#7c3aed';
      $boxes[] = ['label' => $row['STATUS'], 'value' => $row['TOTAL'], 'color' => $color, 'bg' => $color . '18'];
    }
    $boxes[] = ['label' => 'Total Assets', 'value' => $total, 'color' => '#7c3aed', 'bg' => '#7c3aed18'];

    $statusRows = '';
    foreach ($rows as $i => $row) {
      $bg    = $i % 2 === 0 ? '#ffffff' : '#f9f5ff';
      $color = $statusColors[$row['STATUS']] ?? '#333';
      $pct   = $total > 0 ? round($row['TOTAL'] / $total * 100, 1) : 0;
      $statusRows .= '
        <tr style="background:' . $bg . ';">
          <td style="padding:10px 14px;">
            <span style="display:inline-block;padding:3px 10px;border-radius:20px;
              background:' . $color . '18;color:' . $color . ';font-weight:600;font-size:11px;">
              ' . $row['STATUS'] . '
            </span>
          </td>
          <td style="padding:10px 14px;text-align:center;font-weight:700;color:' . $color . ';">' . $row['TOTAL'] . '</td>
          <td style="padding:10px 14px;text-align:center;">' . $pct . '%</td>
        </tr>
      ';
    }

    $categoryRows = '';
    foreach ($byCategory as $i => $row) {
      $bg    = $i % 2 === 0 ? '#ffffff' : '#f9f5ff';
      $color = $categoryColors[$i % count($categoryColors)];
      $pct   = $total > 0 ? round($row['TOTAL'] / $total * 100, 1) : 0;
      $categoryRows .= '
        <tr style="background:' . $bg . ';">
          <td style="padding:10px 14px;font-weight:600;">' . htmlspecialchars($row['LABEL'] ?? '—') . '</td>
          <td style="padding:10px 14px;text-align:center;font-weight:700;color:' . $color . ';">' . $row['TOTAL'] . '</td>
          <td style="padding:10px 14px;text-align:center;">' . $pct . '%</td>
        </tr>
      ';
    }

    $typeRows = '';
    foreach ($byItemType as $i => $row) {
      $bg    = $i % 2 === 0 ? '#ffffff' : '#f9f5ff';
      $color = $typeColors[$i % count($typeColors)];
      $pct   = $total > 0 ? round($row['TOTAL'] / $total * 100, 1) : 0;
      $typeRows .= '
        <tr style="background:' . $bg . ';">
          <td style="padding:10px 14px;font-weight:600;">' . htmlspecialchars($row['LABEL'] ?? '—') . '</td>
          <td style="padding:10px 14px;text-align:center;font-weight:700;color:' . $color . ';">' . $row['TOTAL'] . '</td>
          <td style="padding:10px 14px;text-align:center;">' . $pct . '%</td>
        </tr>
      ';
    }

    $html = self::buildPageHTML(
      self::buildHeader('Complete Asset Inventory Report', 'Full breakdown by status, category, and item type') .
        self::buildSummaryBoxes($boxes) .
        '<table style="width:100%;margin-bottom:24px;"><tr>
        <td style="text-align:center;width:33%;"><img src="' . $statusChartImg   . '" style="max-width:100%;"></td>
        <td style="text-align:center;width:33%;"><img src="' . $categoryChartImg . '" style="max-width:100%;"></td>
        <td style="text-align:center;width:33%;"><img src="' . $typeChartImg     . '" style="max-width:100%;"></td>
      </tr></table>' .
        '<h3 style="color:#2d1b47;margin:0 0 10px;">Asset Status Breakdown</h3>
      <table style="width:100%;border-collapse:collapse;font-size:12px;margin-bottom:24px;">
        <thead><tr style="background:#2d1b47;color:#fff;">
          <th style="padding:10px 14px;text-align:left;">STATUS</th>
          <th style="padding:10px 14px;text-align:center;">COUNT</th>
          <th style="padding:10px 14px;text-align:center;">PERCENTAGE</th>
        </tr></thead>
        <tbody>' . $statusRows . '</tbody>
      </table>' .
        '<h3 style="color:#2d1b47;margin:0 0 10px;">By Category</h3>
      <table style="width:100%;border-collapse:collapse;font-size:12px;margin-bottom:24px;">
        <thead><tr style="background:#2d1b47;color:#fff;">
          <th style="padding:10px 14px;text-align:left;">CATEGORY</th>
          <th style="padding:10px 14px;text-align:center;">COUNT</th>
          <th style="padding:10px 14px;text-align:center;">PERCENTAGE</th>
        </tr></thead>
        <tbody>' . $categoryRows . '</tbody>
      </table>' .
        '<h3 style="color:#2d1b47;margin:0 0 10px;">By Item Type</h3>
      <table style="width:100%;border-collapse:collapse;font-size:12px;margin-bottom:24px;">
        <thead><tr style="background:#2d1b47;color:#fff;">
          <th style="padding:10px 14px;text-align:left;">ITEM TYPE</th>
          <th style="padding:10px 14px;text-align:center;">COUNT</th>
          <th style="padding:10px 14px;text-align:center;">PERCENTAGE</th>
        </tr></thead>
        <tbody>' . $typeRows . '</tbody>
      </table>' .
        self::buildFooter()
    );

    self::makePDF($html, 'Complete_Inventory_Report_' . date('Ymd'));
  }

  public static function exportAssetStatusReport($data)
  {
    $rows  = $data['rows'];
    $total = 0;

    $statusColors = [
      'Available'   => '#15803d',
      'In Use'      => '#1d4ed8',
      'Maintenance' => '#b45309',
    ];

    $slices = [];
    foreach ($rows as $row) {
      $total    += (int)$row['TOTAL'];
      $slices[]  = [
        'label' => $row['STATUS'],
        'value' => (int)$row['TOTAL'],
        'color' => $statusColors[$row['STATUS']] ?? '#7c3aed',
      ];
    }

    $chartImg = self::buildPieChart($slices, 'Asset Distribution by Status');

    $boxes = [];
    foreach ($rows as $row) {
      $color   = $statusColors[$row['STATUS']] ?? '#7c3aed';
      $boxes[] = ['label' => $row['STATUS'], 'value' => $row['TOTAL'], 'color' => $color, 'bg' => $color . '18'];
    }
    $boxes[] = ['label' => 'Total Assets', 'value' => $total, 'color' => '#7c3aed', 'bg' => '#7c3aed18'];

    $tableRows = '';
    foreach ($rows as $i => $row) {
      $bg    = $i % 2 === 0 ? '#ffffff' : '#f9f5ff';
      $color = $statusColors[$row['STATUS']] ?? '#333';
      $pct   = $total > 0 ? round($row['TOTAL'] / $total * 100, 1) : 0;
      $tableRows .= '
        <tr style="background:' . $bg . ';">
          <td style="padding:10px 14px;">
            <span style="display:inline-block;padding:3px 10px;border-radius:20px;
              background:' . $color . '18;color:' . $color . ';font-weight:600;font-size:11px;">
              ' . $row['STATUS'] . '
            </span>
          </td>
          <td style="padding:10px 14px;text-align:center;font-weight:700;color:' . $color . ';">' . $row['TOTAL'] . '</td>
          <td style="padding:10px 14px;text-align:center;">' . $pct . '%</td>
        </tr>
      ';
    }

    $html = self::buildPageHTML(
      self::buildHeader('Asset Status Report', 'Overview of all asset statuses') .
        self::buildSummaryBoxes($boxes) .
        '<img src="' . $chartImg . '" style="display:block;margin:0 auto 24px;max-width:100%;">' .
        '<table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead><tr style="background:#2d1b47;color:#fff;">
          <th style="padding:10px 14px;text-align:left;">STATUS</th>
          <th style="padding:10px 14px;text-align:center;">COUNT</th>
          <th style="padding:10px 14px;text-align:center;">PERCENTAGE</th>
        </tr></thead>
        <tbody>' . $tableRows . '</tbody>
      </table>' .
        self::buildFooter()
    );

    self::makePDF($html, 'Asset_Status_Report_' . date('Ymd'));
  }

  public static function exportAssetByDepartmentReport($data)
  {
    $rows     = $data['rows'];
    $deptName = $data['dept_name'] ?? 'All Departments';

    $groups     = [];
    $grandTotal = 0;
    $totAvail   = 0;
    $totInUse   = 0;
    $totMaint   = 0;

    foreach ($rows as $row) {
      $label    = strlen($row['DEPARTMENT_NAME']) > 6
        ? substr($row['DEPARTMENT_NAME'], 0, 6) . '.'
        : $row['DEPARTMENT_NAME'];
      $groups[] = [
        'label'  => $label,
        'values' => [(int)$row['AVAILABLE'], (int)$row['IN_USE'], (int)$row['MAINTENANCE']],
        'colors' => ['#15803d', '#1d4ed8', '#b45309'],
      ];
      $grandTotal += (int)$row['TOTAL'];
      $totAvail   += (int)$row['AVAILABLE'];
      $totInUse   += (int)$row['IN_USE'];
      $totMaint   += (int)$row['MAINTENANCE'];
    }

    $chartImg = self::buildBarChart($groups, ['Available', 'In Use', 'Maintenance'], 'Assets by Department');

    $boxes = [
      ['label' => 'Total Assets',    'value' => $grandTotal, 'color' => '#7c3aed', 'bg' => '#7c3aed18'],
      ['label' => 'Total Available', 'value' => $totAvail,   'color' => '#15803d', 'bg' => '#15803d18'],
      ['label' => 'Total In Use',    'value' => $totInUse,   'color' => '#1d4ed8', 'bg' => '#1d4ed818'],
      ['label' => 'Maintenance',     'value' => $totMaint,   'color' => '#b45309', 'bg' => '#b4530918'],
    ];

    $tableRows = '';
    foreach ($rows as $i => $row) {
      $bg = $i % 2 === 0 ? '#ffffff' : '#f9f5ff';
      $tableRows .= '
        <tr style="background:' . $bg . ';">
          <td style="padding:10px 14px;font-weight:600;">'                                . $row['DEPARTMENT_NAME'] . '</td>
          <td style="padding:10px 14px;text-align:center;font-weight:700;color:#7c3aed;">' . $row['TOTAL']          . '</td>
          <td style="padding:10px 14px;text-align:center;color:#15803d;font-weight:600;">' . $row['AVAILABLE']      . '</td>
          <td style="padding:10px 14px;text-align:center;color:#1d4ed8;font-weight:600;">' . $row['IN_USE']         . '</td>
          <td style="padding:10px 14px;text-align:center;color:#b45309;font-weight:600;">' . $row['MAINTENANCE']    . '</td>
        </tr>
      ';
    }

    $html = self::buildPageHTML(
      self::buildHeader('Asset by Department Report', $deptName) .
        self::buildSummaryBoxes($boxes) .
        '<img src="' . $chartImg . '" style="display:block;margin:0 auto 24px;max-width:100%;">' .
        '<table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead><tr style="background:#2d1b47;color:#fff;">
          <th style="padding:10px 14px;text-align:left;">DEPARTMENT</th>
          <th style="padding:10px 14px;text-align:center;">TOTAL</th>
          <th style="padding:10px 14px;text-align:center;">AVAILABLE</th>
          <th style="padding:10px 14px;text-align:center;">IN USE</th>
          <th style="padding:10px 14px;text-align:center;">MAINTENANCE</th>
        </tr></thead>
        <tbody>' . $tableRows . '</tbody>
      </table>' .
        self::buildFooter()
    );

    self::makePDF($html, 'Asset_By_Department_' . date('Ymd'));
  }

  public static function exportCertifiedAssetsReport($data)
  {
    $rows     = $data['rows'];
    $deptName = $data['dept_name'] ?? 'All Departments';

    $groups     = [];
    $grandTotal = 0;
    $totalCert  = 0;

    foreach ($rows as $row) {
      $label    = strlen($row['DEPARTMENT_NAME']) > 6
        ? substr($row['DEPARTMENT_NAME'], 0, 6) . '.'
        : $row['DEPARTMENT_NAME'];
      $groups[] = [
        'label'  => $label,
        'values' => [(int)$row['CERTIFIED'], (int)$row['NOT_CERTIFIED']],
        'colors' => ['#15803d', '#dc2626'],
      ];
      $grandTotal += (int)$row['TOTAL'];
      $totalCert  += (int)$row['CERTIFIED'];
    }

    $chartImg = self::buildBarChart($groups, ['Certified', 'Not Certified'], 'Certified vs Non-Certified by Dept');

    $certPct = $grandTotal > 0 ? round($totalCert / $grandTotal * 100, 1) : 0;
    $boxes   = [
      ['label' => 'Total Assets',       'value' => $grandTotal,              'color' => '#7c3aed', 'bg' => '#7c3aed18'],
      ['label' => 'Certified',          'value' => $totalCert,               'color' => '#15803d', 'bg' => '#15803d18'],
      ['label' => 'Not Certified',      'value' => $grandTotal - $totalCert, 'color' => '#dc2626', 'bg' => '#dc262618'],
      ['label' => 'Certification Rate', 'value' => $certPct . '%',           'color' => '#1d4ed8', 'bg' => '#1d4ed818'],
    ];

    $tableRows = '';
    foreach ($rows as $i => $row) {
      $bg  = $i % 2 === 0 ? '#ffffff' : '#f9f5ff';
      $pct = $row['TOTAL'] > 0 ? round($row['CERTIFIED'] / $row['TOTAL'] * 100, 1) : 0;
      $tableRows .= '
        <tr style="background:' . $bg . ';">
          <td style="padding:10px 14px;font-weight:600;">'                                . $row['DEPARTMENT_NAME'] . '</td>
          <td style="padding:10px 14px;text-align:center;color:#7c3aed;font-weight:700;">' . $row['TOTAL']          . '</td>
          <td style="padding:10px 14px;text-align:center;color:#15803d;font-weight:600;">' . $row['CERTIFIED']      . '</td>
          <td style="padding:10px 14px;text-align:center;color:#dc2626;font-weight:600;">' . $row['NOT_CERTIFIED']  . '</td>
          <td style="padding:10px 14px;text-align:center;">'                              . $pct . '%'              . '</td>
        </tr>
      ';
    }

    $html = self::buildPageHTML(
      self::buildHeader('Certified Assets Report', $deptName) .
        self::buildSummaryBoxes($boxes) .
        '<img src="' . $chartImg . '" style="display:block;margin:0 auto 24px;max-width:100%;">' .
        '<table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead><tr style="background:#2d1b47;color:#fff;">
          <th style="padding:10px 14px;text-align:left;">DEPARTMENT</th>
          <th style="padding:10px 14px;text-align:center;">TOTAL</th>
          <th style="padding:10px 14px;text-align:center;">CERTIFIED</th>
          <th style="padding:10px 14px;text-align:center;">NOT CERTIFIED</th>
          <th style="padding:10px 14px;text-align:center;">RATE</th>
        </tr></thead>
        <tbody>' . $tableRows . '</tbody>
      </table>' .
        self::buildFooter()
    );

    self::makePDF($html, 'Certified_Assets_Report_' . date('Ymd'));
  }

  public static function exportOverdueItemsReport($data)
  {
    $overdueBorrows = $data['overdue_borrows'];
    $lateReturns    = $data['late_returns'];
    $overdueMaint   = $data['overdue_maint']   ?? [];
    $overdueActive  = $data['overdue_active']  ?? [];
    $summary        = $data['summary'];
    $scope          = $data['scope'];

    $groups = [
      ['label' => 'Overdue',   'values' => [$summary['overdue_borrows']], 'colors' => ['#dc2626']],
      ['label' => 'Late Ret',  'values' => [$summary['late_returns']],    'colors' => ['#b45309']],
      ['label' => 'Ov.Maint',  'values' => [$summary['overdue_maint']],   'colors' => ['#7c3aed']],
    ];
    $chartImg = self::buildBarChart($groups, ['Overdue Borrows', 'Late Returns', 'Overdue Maint'], 'Overdue Items Summary');

    $boxes = [
      ['label' => 'Overdue Borrows',     'value' => $summary['overdue_borrows'], 'color' => '#dc2626', 'bg' => '#dc262618'],
      ['label' => 'Late Returns',        'value' => $summary['late_returns'],    'color' => '#b45309', 'bg' => '#b4530918'],
      ['label' => 'Overdue Maintenance', 'value' => $summary['overdue_maint'],   'color' => '#7c3aed', 'bg' => '#7c3aed18'],
    ];

    $html = self::buildHeader('Overdue Items Report', 'Scope: ' . ucfirst($scope)) .
      self::buildSummaryBoxes($boxes) .
      '<img src="' . $chartImg . '" style="display:block;margin:0 auto 24px;max-width:100%;">';

    if (!empty($overdueBorrows)) {
      $rows = '';
      foreach ($overdueBorrows as $i => $b) {
        $bg       = $i % 2 === 0 ? '#ffffff' : '#fff5f5';
        $borrower = trim(implode(' ', array_filter([$b['FIRST_NAME'], $b['MIDDLE_NAME'], $b['LAST_NAME'], $b['SUFFIX']])));
        $rows .= '
          <tr style="background:' . $bg . ';">
            <td style="padding:8px 12px;">' . $b['BORROW_ID']         . '</td>
            <td style="padding:8px 12px;">' . $b['ASSET_ID']          . '</td>
            <td style="padding:8px 12px;">' . $b['ASSET_DESCRIPTION'] . '</td>
            <td style="padding:8px 12px;">' . $b['DEPARTMENT_NAME']   . '</td>
            <td style="padding:8px 12px;">' . $borrower               . '</td>
            <td style="padding:8px 12px;">' . $b['DUE_DATE']          . '</td>
          </tr>
        ';
      }
      $html .= '
        <h3 style="color:#dc2626;margin:20px 0 10px;">Overdue Borrows</h3>
        <table style="width:100%;border-collapse:collapse;font-size:11px;margin-bottom:24px;">
          <thead><tr style="background:#dc2626;color:#fff;">
            <th style="padding:8px 12px;text-align:left;">BORROW ID</th>
            <th style="padding:8px 12px;text-align:left;">ASSET ID</th>
            <th style="padding:8px 12px;text-align:left;">DESCRIPTION</th>
            <th style="padding:8px 12px;text-align:left;">DEPARTMENT</th>
            <th style="padding:8px 12px;text-align:left;">BORROWER</th>
            <th style="padding:8px 12px;text-align:left;">DUE DATE</th>
          </tr></thead>
          <tbody>' . $rows . '</tbody>
        </table>
      ';
    } else {
      $html .= '<p style="color:#888;font-size:12px;margin:12px 0 24px;">No overdue borrows found.</p>';
    }

    if (!empty($lateReturns)) {
      $rows = '';
      foreach ($lateReturns as $i => $b) {
        $bg       = $i % 2 === 0 ? '#ffffff' : '#fffbeb';
        $borrower = trim(implode(' ', array_filter([$b['FIRST_NAME'], $b['MIDDLE_NAME'], $b['LAST_NAME'], $b['SUFFIX']])));
        $rows .= '
          <tr style="background:' . $bg . ';">
            <td style="padding:8px 12px;">' . $b['BORROW_ID']         . '</td>
            <td style="padding:8px 12px;">' . $b['ASSET_ID']          . '</td>
            <td style="padding:8px 12px;">' . $b['ASSET_DESCRIPTION'] . '</td>
            <td style="padding:8px 12px;">' . $borrower               . '</td>
            <td style="padding:8px 12px;">' . $b['DUE_DATE']          . '</td>
            <td style="padding:8px 12px;">' . $b['RETURN_DATE']       . '</td>
          </tr>
        ';
      }
      $html .= '
        <h3 style="color:#b45309;margin:20px 0 10px;">Late Returns</h3>
        <table style="width:100%;border-collapse:collapse;font-size:11px;margin-bottom:24px;">
          <thead><tr style="background:#b45309;color:#fff;">
            <th style="padding:8px 12px;text-align:left;">BORROW ID</th>
            <th style="padding:8px 12px;text-align:left;">ASSET ID</th>
            <th style="padding:8px 12px;text-align:left;">DESCRIPTION</th>
            <th style="padding:8px 12px;text-align:left;">BORROWER</th>
            <th style="padding:8px 12px;text-align:left;">DUE DATE</th>
            <th style="padding:8px 12px;text-align:left;">RETURN DATE</th>
          </tr></thead>
          <tbody>' . $rows . '</tbody>
        </table>
      ';
    } else {
      $html .= '<p style="color:#888;font-size:12px;margin:12px 0 24px;">No late returns found.</p>';
    }

    if (!empty($overdueMaint)) {
      $rows = '';
      foreach ($overdueMaint as $i => $m) {
        $bg = $i % 2 === 0 ? '#ffffff' : '#faf5ff';
        $rows .= '
          <tr style="background:' . $bg . ';">
            <td style="padding:8px 12px;">' . $m['MAINTENANCE_ID']    . '</td>
            <td style="padding:8px 12px;">' . $m['ASSET_ID']          . '</td>
            <td style="padding:8px 12px;">' . $m['ASSET_DESCRIPTION'] . '</td>
            <td style="padding:8px 12px;">' . $m['DEPARTMENT_NAME']   . '</td>
            <td style="padding:8px 12px;">' . $m['MAINTENANCE_TYPE']  . '</td>
            <td style="padding:8px 12px;">' . $m['SCHEDULED_DATE']    . '</td>
          </tr>
        ';
      }
      $html .= '
        <h3 style="color:#7c3aed;margin:20px 0 10px;">Overdue Maintenance</h3>
        <table style="width:100%;border-collapse:collapse;font-size:11px;margin-bottom:24px;">
          <thead><tr style="background:#7c3aed;color:#fff;">
            <th style="padding:8px 12px;text-align:left;">MAINT ID</th>
            <th style="padding:8px 12px;text-align:left;">ASSET ID</th>
            <th style="padding:8px 12px;text-align:left;">DESCRIPTION</th>
            <th style="padding:8px 12px;text-align:left;">DEPARTMENT</th>
            <th style="padding:8px 12px;text-align:left;">TYPE</th>
            <th style="padding:8px 12px;text-align:left;">SCHEDULED DATE</th>
          </tr></thead>
          <tbody>' . $rows . '</tbody>
        </table>
      ';
    }

    $html .= self::buildFooter();
    self::makePDF(self::buildPageHTML($html), 'Overdue_Items_Report_' . date('Ymd'));
  }

  public static function exportBorrowingActivityReport($data)
  {
    $summary = $data['summary'];
    $records = $data['records'];

    $statusColors = [
      'Pending'   => '#b45309',
      'Borrowed'  => '#1d4ed8',
      'Returned'  => '#15803d',
      'Overdue'   => '#dc2626',
      'Cancelled' => '#6b7280',
    ];

    $groups       = [];
    $boxes        = [];
    $legendLabels = [];
    $total        = 0;

    foreach ($summary as $row) {
      $color          = $statusColors[$row['STATUS']] ?? '#7c3aed';
      $groups[]       = ['label' => $row['STATUS'], 'values' => [(int)$row['TOTAL']], 'colors' => [$color]];
      $legendLabels[] = $row['STATUS'];
      $boxes[]        = ['label' => $row['STATUS'], 'value' => $row['TOTAL'], 'color' => $color, 'bg' => $color . '18'];
      $total         += (int)$row['TOTAL'];
    }

    $chartImg  = self::buildBarChart($groups, $legendLabels, 'Borrowing Activity by Status');
    $tableRows = '';

    foreach ($records as $i => $b) {
      $bg       = $i % 2 === 0 ? '#ffffff' : '#f9f5ff';
      $color    = $statusColors[$b['STATUS']] ?? '#333';
      $borrower = trim(implode(' ', array_filter([$b['FIRST_NAME'], $b['MIDDLE_NAME'], $b['LAST_NAME'], $b['SUFFIX']])));
      $tableRows .= '
        <tr style="background:' . $bg . ';">
          <td style="padding:8px 12px;">' . $b['BORROW_ID']                 . '</td>
          <td style="padding:8px 12px;">' . $b['ASSET_ID']                  . '</td>
          <td style="padding:8px 12px;">' . $b['ASSET_DESCRIPTION']         . '</td>
          <td style="padding:8px 12px;">' . $b['DEPARTMENT_NAME']           . '</td>
          <td style="padding:8px 12px;">' . $borrower                       . '</td>
          <td style="padding:8px 12px;">' . $b['BORROW_DATE']               . '</td>
          <td style="padding:8px 12px;">' . $b['DUE_DATE']                  . '</td>
          <td style="padding:8px 12px;">' . ($b['RETURN_DATE'] ?: '—')      . '</td>
          <td style="padding:8px 12px;">
            <span style="display:inline-block;padding:2px 8px;border-radius:20px;
              background:' . $color . '18;color:' . $color . ';font-weight:600;font-size:10px;">
              ' . $b['STATUS'] . '
            </span>
          </td>
        </tr>
      ';
    }

    $html = self::buildPageHTML(
      self::buildHeader('Borrowing Activity Report', 'Latest 50 borrow records') .
        self::buildSummaryBoxes($boxes) .
        '<img src="' . $chartImg . '" style="display:block;margin:0 auto 24px;max-width:100%;">' .
        '<table style="width:100%;border-collapse:collapse;font-size:11px;">
        <thead><tr style="background:#2d1b47;color:#fff;">
          <th style="padding:8px 12px;text-align:left;">ID</th>
          <th style="padding:8px 12px;text-align:left;">ASSET ID</th>
          <th style="padding:8px 12px;text-align:left;">DESCRIPTION</th>
          <th style="padding:8px 12px;text-align:left;">DEPARTMENT</th>
          <th style="padding:8px 12px;text-align:left;">BORROWER</th>
          <th style="padding:8px 12px;text-align:left;">BORROW DATE</th>
          <th style="padding:8px 12px;text-align:left;">DUE DATE</th>
          <th style="padding:8px 12px;text-align:left;">RETURN DATE</th>
          <th style="padding:8px 12px;text-align:left;">STATUS</th>
        </tr></thead>
        <tbody>' . $tableRows . '</tbody>
      </table>' .
        self::buildFooter()
    );

    self::makePDF($html, 'Borrowing_Activity_Report_' . date('Ymd'));
  }

  public static function exportAssetUtilizationReport($data)
  {
    $rows    = $data['rows'];
    $overall = $data['overall'];

    $groups = [];
    foreach ($rows as $row) {
      $label    = strlen($row['DEPARTMENT_NAME']) > 6
        ? substr($row['DEPARTMENT_NAME'], 0, 6) . '.'
        : $row['DEPARTMENT_NAME'];
      $groups[] = [
        'label'  => $label,
        'values' => [(float)($row['UTILIZATION_PCT'] ?? 0)],
        'colors' => ['#7c3aed'],
      ];
    }

    $chartImg = self::buildBarChart($groups, ['Utilization %'], 'Asset Utilization Rate by Department (%)');

    $boxes = [
      ['label' => 'Total Assets',        'value' => $overall['TOTAL'],                 'color' => '#7c3aed', 'bg' => '#7c3aed18'],
      ['label' => 'In Use',              'value' => $overall['IN_USE'],                'color' => '#1d4ed8', 'bg' => '#1d4ed818'],
      ['label' => 'Available',           'value' => $overall['AVAILABLE'],             'color' => '#15803d', 'bg' => '#15803d18'],
      ['label' => 'Overall Utilization', 'value' => $overall['UTILIZATION_PCT'] . '%', 'color' => '#b45309', 'bg' => '#b4530918'],
    ];

    $tableRows = '';
    foreach ($rows as $i => $row) {
      $bg       = $i % 2 === 0 ? '#ffffff' : '#f9f5ff';
      $pct      = $row['UTILIZATION_PCT'] ?? 0;
      $pctColor = $pct >= 75 ? '#15803d' : ($pct >= 40 ? '#b45309' : '#dc2626');
      $tableRows .= '
        <tr style="background:' . $bg . ';">
          <td style="padding:10px 14px;font-weight:600;">'                                . $row['DEPARTMENT_NAME'] . '</td>
          <td style="padding:10px 14px;text-align:center;color:#7c3aed;font-weight:700;">' . $row['TOTAL']          . '</td>
          <td style="padding:10px 14px;text-align:center;color:#1d4ed8;font-weight:600;">' . $row['IN_USE']         . '</td>
          <td style="padding:10px 14px;text-align:center;color:#15803d;font-weight:600;">' . $row['AVAILABLE']      . '</td>
          <td style="padding:10px 14px;text-align:center;color:#b45309;font-weight:600;">' . $row['MAINTENANCE']    . '</td>
          <td style="padding:10px 14px;text-align:center;">
            <span style="font-weight:700;color:' . $pctColor . ';font-size:13px;">' . $pct . '%</span>
          </td>
        </tr>
      ';
    }

    $html = self::buildPageHTML(
      self::buildHeader('Asset Utilization Report', 'Utilization rate per department') .
        self::buildSummaryBoxes($boxes) .
        '<img src="' . $chartImg . '" style="display:block;margin:0 auto 24px;max-width:100%;">' .
        '<table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead><tr style="background:#2d1b47;color:#fff;">
          <th style="padding:10px 14px;text-align:left;">DEPARTMENT</th>
          <th style="padding:10px 14px;text-align:center;">TOTAL</th>
          <th style="padding:10px 14px;text-align:center;">IN USE</th>
          <th style="padding:10px 14px;text-align:center;">AVAILABLE</th>
          <th style="padding:10px 14px;text-align:center;">MAINTENANCE</th>
          <th style="padding:10px 14px;text-align:center;">UTILIZATION %</th>
        </tr></thead>
        <tbody>' . $tableRows . '</tbody>
      </table>' .
        self::buildFooter()
    );

    self::makePDF($html, 'Asset_Utilization_Report_' . date('Ymd'));
  }

  public static function exportMaintenanceReport($data)
  {
    $summary = $data['summary'] ?? [];
    $byType  = $data['by_type'] ?? [];
    $period  = $data['period']  ?? '';

    $subtitle = $period
      ? 'Maintenance status summary — ' . $period
      : 'Maintenance status summary — All Time';

    $counts = ['Pending' => 0, 'In Progress' => 0, 'Completed' => 0, 'Cancelled' => 0];
    foreach ($summary as $row) {
      $s = $row['STATUS'] ?? '';
      if (isset($counts[$s])) $counts[$s] = (int)$row['TOTAL'];
    }

    $boxes = [
      ['label' => 'PENDING',     'value' => $counts['Pending'],     'color' => '#b45309', 'bg' => '#b4530918'],
      ['label' => 'IN PROGRESS', 'value' => $counts['In Progress'], 'color' => '#1d4ed8', 'bg' => '#1d4ed818'],
      ['label' => 'COMPLETED',   'value' => $counts['Completed'],   'color' => '#15803d', 'bg' => '#15803d18'],
      ['label' => 'CANCELLED',   'value' => $counts['Cancelled'],   'color' => '#6b7280', 'bg' => '#6b728018'],
    ];

    $slices = [
      ['label' => 'Pending',     'value' => $counts['Pending'],     'color' => '#b45309'],
      ['label' => 'In Progress', 'value' => $counts['In Progress'], 'color' => '#1d4ed8'],
      ['label' => 'Completed',   'value' => $counts['Completed'],   'color' => '#15803d'],
      ['label' => 'Cancelled',   'value' => $counts['Cancelled'],   'color' => '#6b7280'],
    ];

    $chartImg  = self::buildPieChart($slices, 'Maintenance by Status');
    $tableRows = '';

    foreach ($byType as $row) {
      $tableRows .= '
        <tr style="border-bottom:1px solid #e5e7eb;">
          <td style="padding:10px 14px;">'                                               . htmlspecialchars($row['MAINTENANCE_TYPE'] ?? '—') . '</td>
          <td style="padding:10px 14px;text-align:center;">'                             . ($row['TOTAL'] ?? 0)       . '</td>
          <td style="padding:10px 14px;text-align:center;color:#b45309;font-weight:600;">' . ($row['PENDING'] ?? 0)     . '</td>
          <td style="padding:10px 14px;text-align:center;color:#1d4ed8;font-weight:600;">' . ($row['IN_PROGRESS'] ?? 0) . '</td>
          <td style="padding:10px 14px;text-align:center;color:#15803d;font-weight:600;">' . ($row['COMPLETED'] ?? 0)   . '</td>
          <td style="padding:10px 14px;text-align:center;color:#6b7280;font-weight:600;">' . ($row['CANCELLED'] ?? 0)   . '</td>
        </tr>
      ';
    }

    if (!$tableRows) {
      $tableRows = '<tr><td colspan="6" style="padding:20px;text-align:center;color:#888;">No maintenance records found.</td></tr>';
    }

    $html = self::buildPageHTML(
      self::buildHeader('Maintenance Report', $subtitle) .
        self::buildSummaryBoxes($boxes) .
        '<img src="' . $chartImg . '" style="display:block;margin:0 auto 24px;max-width:100%;">' .
        '<table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead><tr style="background:#2d1b47;color:#fff;">
          <th style="padding:10px 14px;text-align:left;">MAINTENANCE TYPE</th>
          <th style="padding:10px 14px;text-align:center;">TOTAL</th>
          <th style="padding:10px 14px;text-align:center;">PENDING</th>
          <th style="padding:10px 14px;text-align:center;">IN PROGRESS</th>
          <th style="padding:10px 14px;text-align:center;">COMPLETED</th>
          <th style="padding:10px 14px;text-align:center;">CANCELLED</th>
        </tr></thead>
        <tbody>' . $tableRows . '</tbody>
      </table>' .
        self::buildFooter()
    );

    self::makePDF($html, 'Maintenance_Report_' . date('Ymd'));
  }
}
