<?php
// backend/services/MaintenanceExportService.php
use Dompdf\Dompdf;
use Dompdf\Options;

class MaintenanceExportService {

  // EXPORT PDF
  public static function exportPDF($maintenance) {
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isHtml5ParserEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml(self::buildPDFHtml($maintenance));
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $filename = 'ONEQCU_Maintenance_' . date('Ymd_His') . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
  }

  // BUILD PDF HTML
  private static function buildPDFHtml($maintenance) {
    $rows      = self::buildRows($maintenance);
    $total     = count($maintenance);
    $generated = date('F d, Y h:i A');

    $pending     = count(array_filter($maintenance, fn($m) => $m['STATUS'] === 'Pending'));
    $in_progress = count(array_filter($maintenance, fn($m) => $m['STATUS'] === 'In Progress'));
    $completed   = count(array_filter($maintenance, fn($m) => $m['STATUS'] === 'Completed'));
    $cancelled   = count(array_filter($maintenance, fn($m) => $m['STATUS'] === 'Cancelled'));

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
        tr:last-child td      { border-bottom:none; }

        .report-footer       { margin-top:16px; padding-top:8px;
                               border-top:1px solid #e5e7eb; font-size:7.5px;
                               color:#9ca3af; text-align:center; }
      </style>
    </head>
    <body>

      <div class="report-header">
        <div class="report-header-left">
          <div class="report-title">ONEQCU</div>
          <div class="report-subtitle">MAINTENANCE REPORT</div>
        </div>
        <div class="report-header-right">
          <div class="report-meta">
            <span>Generated:</span> ' . $generated . '<br>
            <span>Report:</span> Maintenance Records Report<br>
            <span>Total Records:</span> ' . $total . '
          </div>
        </div>
      </div>

      <div class="summary-row">
        <div class="summary-box">
          <div class="summary-label">Pending</div>
          <div class="summary-value" style="color:#b45309;">' . $pending . '</div>
        </div>
        <div class="summary-box">
          <div class="summary-label">In Progress</div>
          <div class="summary-value" style="color:#1d4ed8;">' . $in_progress . '</div>
        </div>
        <div class="summary-box">
          <div class="summary-label">Completed</div>
          <div class="summary-value" style="color:#15803d;">' . $completed . '</div>
        </div>
        <div class="summary-box">
          <div class="summary-label">Cancelled</div>
          <div class="summary-value" style="color:#6b7280;">' . $cancelled . '</div>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Asset ID</th>
            <th>Description</th>
            <th>Department</th>
            <th>Type</th>
            <th>Issue</th>
            <th>Technician</th>
            <th>Scheduled</th>
            <th>Completed</th>
            <th>Status</th>
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
  private static function buildRows($maintenance) {
    $rows = '';

    foreach ($maintenance as $m) {
      $technician = implode(' ', array_filter([
        $m['TECH_FIRST_NAME']  ?? '',
        $m['TECH_MIDDLE_NAME'] ?? '',
        $m['TECH_LAST_NAME']   ?? '',
        $m['TECH_SUFFIX']      ?? '',
      ])) ?: '—';

      $status       = $m['STATUS'] ?? '—';
      $status_color = '#374151';
      if ($status === 'Pending')     $status_color = '#b45309';
      if ($status === 'In Progress') $status_color = '#1d4ed8';
      if ($status === 'Completed')   $status_color = '#15803d';
      if ($status === 'Cancelled')   $status_color = '#6b7280';

      $rows .= '
        <tr>
          <td>' . htmlspecialchars($m['MAINTENANCE_ID']    ?? '—') . '</td>
          <td>' . htmlspecialchars($m['ASSET_ID']          ?? '—') . '</td>
          <td>' . htmlspecialchars($m['ASSET_DESCRIPTION'] ?? '—') . '</td>
          <td>' . htmlspecialchars($m['DEPARTMENT_NAME']   ?? '—') . '</td>
          <td>' . htmlspecialchars($m['MAINTENANCE_TYPE']  ?? '—') . '</td>
          <td>' . htmlspecialchars($m['ISSUE_DESCRIPTION'] ?? '—') . '</td>
          <td>' . htmlspecialchars($technician)                     . '</td>
          <td>' . htmlspecialchars($m['SCHEDULED_DATE']    ?? '—') . '</td>
          <td>' . htmlspecialchars($m['COMPLETED_DATE']    ?? '—') . '</td>
          <td style="color:' . $status_color . ';font-weight:600;">'
              . htmlspecialchars($status)                           . '</td>
        </tr>';
    }

    return $rows;
  }

}
?>