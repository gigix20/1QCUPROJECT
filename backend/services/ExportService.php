<?php
// backend/services/ExportService.php

use Dompdf\Dompdf;
use Dompdf\Options;

class ExportService {
  // EXPORT PDF
  public static function exportPDF($assets) {
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isHtml5ParserEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml(self::buildPDFHtml($assets));
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $filename = 'ONEQCU_Assets_' . date('Ymd_His') . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
  }

  // BUILD PDF HTML
  private static function buildPDFHtml($assets) {
    $rows      = self::buildRows($assets);
    $total     = count($assets);
    $generated = date('F d, Y h:i A');

    $available   = count(array_filter($assets, fn($a) => $a['STATUS'] === 'Available'));
    $in_use      = count(array_filter($assets, fn($a) => $a['STATUS'] === 'In Use'));
    $maintenance = count(array_filter($assets, fn($a) => $a['STATUS'] === 'Maintenance'));

    return '<!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:Arial,sans-serif; font-size:9px; color:#1f2937; padding:24px; }

        .report-header       { display:table; width:100%; margin-bottom:16px;
                               border-bottom:3px solid #7c3aed; padding-bottom:10px; }
        .report-header-left  { display:table-cell; vertical-align:middle; }
        .report-header-right { display:table-cell; vertical-align:middle; text-align:right; }
        .report-title        { font-size:18px; font-weight:700; color:#2d1b47; letter-spacing:1px; }
        .report-subtitle     { font-size:10px; color:#7c3aed; font-weight:600;
                               letter-spacing:0.5px; margin-top:2px; }
        .report-meta         { font-size:8px; color:#6b7280; line-height:1.6; }
        .report-meta span    { font-weight:600; color:#374151; }

        .summary-row         { display:table; width:100%; margin-bottom:14px; }
        .summary-box         { display:table-cell; width:25%; padding:8px 12px;
                               border-radius:6px; border:1px solid #e5e7eb; }
        .summary-box + .summary-box { padding-left:8px; }
        .summary-label       { font-size:7px; color:#9ca3af; font-weight:600;
                               text-transform:uppercase; letter-spacing:0.5px; }
        .summary-value       { font-size:16px; font-weight:700; margin-top:2px; }

        table                { width:100%; border-collapse:collapse; }
        thead tr             { background:#2d1b47; }
        th                   { padding:7px 8px; text-align:left; font-size:8px;
                               font-weight:700; color:#fff; letter-spacing:0.5px;
                               text-transform:uppercase; }
        td                   { padding:6px 8px; border-bottom:1px solid #f3f0ff;
                               font-size:8.5px; color:#374151; vertical-align:middle; }
        tr:nth-child(even) td { background:#faf8ff; }
        tr:last-child td     { border-bottom:none; }

        .report-footer       { margin-top:16px; padding-top:8px;
                               border-top:1px solid #e5e7eb; font-size:7.5px;
                               color:#9ca3af; text-align:center; }
      </style>
    </head>
    <body>

      <div class="report-header">
        <div class="report-header-left">
          <div class="report-title">ONEQCU</div>
          <div class="report-subtitle">ASSET MANAGEMENT SYSTEM</div>
        </div>
        <div class="report-header-right">
          <div class="report-meta">
            <span>Generated:</span> ' . $generated . '<br>
            <span>Report:</span> Asset Inventory Report<br>
            <span>Total Assets:</span> ' . $total . '
          </div>
        </div>
      </div>

      <div class="summary-row">
        <div class="summary-box">
          <div class="summary-label">Total Assets</div>
          <div class="summary-value" style="color:#2d1b47;">' . $total . '</div>
        </div>
        <div class="summary-box">
          <div class="summary-label">Available</div>
          <div class="summary-value" style="color:#15803d;">' . $available . '</div>
        </div>
        <div class="summary-box">
          <div class="summary-label">In Use</div>
          <div class="summary-value" style="color:#1d4ed8;">' . $in_use . '</div>
        </div>
        <div class="summary-box">
          <div class="summary-label">Maintenance</div>
          <div class="summary-value" style="color:#b45309;">' . $maintenance . '</div>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Asset ID</th><th>Description</th><th>Serial No.</th>
            <th>Item Type</th><th>Category</th><th>Department</th>
            <th>Liable Person</th><th>Location</th><th>Status</th><th>Certified</th>
          </tr>
        </thead>
        <tbody>' . $rows . '</tbody>
      </table>

      <div class="report-footer">
        ONEQCU Asset Management System &nbsp;|&nbsp;
        This document is system-generated &nbsp;|&nbsp;
        ' . $generated . '
      </div>

    </body>
    </html>';
  }

  // BUILD TABLE ROWS
  private static function buildRows($assets) {
    $rows = '';

    foreach ($assets as $a) {
      $liable = implode(' ', array_filter([
        $a['FIRST_NAME']  ?? '',
        $a['MIDDLE_NAME'] ?? '',
        $a['LAST_NAME']   ?? '',
        $a['SUFFIX']      ?? '',
      ])) ?: '—';

      $status       = $a['STATUS'] ?? '—';
      $status_color = '#374151';
      if ($status === 'Available')   $status_color = '#15803d';
      if ($status === 'In Use')      $status_color = '#1d4ed8';
      if ($status === 'Maintenance') $status_color = '#b45309';

      $certified = $a['IS_CERTIFIED'] == 1
        ? '<span style="color:#854d0e;font-weight:600;">Certified</span>'
        : '—';

      $deleted = $a['IS_DELETED'] == 1
        ? ' <span style="color:#dc2626;font-weight:600;">(Pending)</span>'
        : '';

      $rows .= '
        <tr>
          <td>' . htmlspecialchars($a['ASSET_ID']        ?? '—') . '</td>
          <td>' . htmlspecialchars($a['DESCRIPTION']     ?? '—') . '</td>
          <td>' . htmlspecialchars($a['SERIAL_NUMBER']   ?? '—') . '</td>
          <td>' . htmlspecialchars($a['ITEM_TYPE_NAME']  ?? '—') . '</td>
          <td>' . htmlspecialchars($a['CATEGORY_NAME']   ?? '—') . '</td>
          <td>' . htmlspecialchars($a['DEPARTMENT_NAME'] ?? '—') . '</td>
          <td>' . htmlspecialchars($liable)                       . '</td>
          <td>' . htmlspecialchars($a['LOCATION']        ?? '—') . '</td>
          <td style="color:' . $status_color . ';font-weight:600;">'
              . htmlspecialchars($status) . $deleted              . '</td>
          <td style="text-align:center;">' . $certified           . '</td>
        </tr>';
    }

    return $rows;
  } 
}
?>