<?php
// backend/services/BorrowExportService.php
use Dompdf\Dompdf;
use Dompdf\Options;

class BorrowExportService {

  // EXPORT PDF
  public static function exportPDF($borrows) {
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isHtml5ParserEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml(self::buildPDFHtml($borrows));
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $filename = 'ONEQCU_Borrows_' . date('Ymd_His') . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
  }

  // BUILD PDF HTML
  private static function buildPDFHtml($borrows) {
    $rows      = self::buildRows($borrows);
    $total     = count($borrows);
    $generated = date('F d, Y h:i A');

    $pending  = count(array_filter($borrows, fn($b) => $b['STATUS'] === 'Pending'));
    $active   = count(array_filter($borrows, fn($b) => $b['STATUS'] === 'Borrowed'));
    $overdue = count(array_filter($borrows, fn($b) => $b['STATUS'] === 'Overdue'));
    $overdueReturns = count(array_filter($borrows, function($b) {
        return $b['STATUS'] === 'Returned'
            && !empty($b['RETURN_DATE'])
            && !empty($b['DUE_DATE'])
            && $b['RETURN_DATE'] > $b['DUE_DATE'];
    }));
    $returned = count(array_filter($borrows, fn($b) => $b['STATUS'] === 'Returned'));


    return '<!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:Arial,sans-serif; font-size:12px; color:#1f2937; padding:24px; }

        .report-header       { display:table; width:100%; margin-bottom:16px;
                               border-bottom:3px solid #7c3aed; padding-bottom:10px; }
        .report-header-left  { display:table-cell; vertical-align:middle; }
        .report-header-right { display:table-cell; vertical-align:middle; text-align:right; }
        .report-title        { font-size:18px; font-weight:700; color:#2d1b47; letter-spacing:1px; }
        .report-subtitle     { font-size:10px; color:#7c3aed; font-weight:600;
                               letter-spacing:0.5px; margin-top:2px; }
        .report-meta   { font-size:10px; color:#6b7280; line-height:1.6; }
        .report-meta span    { font-weight:600; color:#374151; }

        .summary-row         { display:table; width:100%; margin-bottom:14px; }
        .summary-box         { display:table-cell; width:20%; padding:8px 12px;
                               border-radius:6px; border:1px solid #e5e7eb; }
        .summary-box + .summary-box { padding-left:8px; }
        .summary-label { font-size:9px; color:#9ca3af; font-weight:600;
                        text-transform:uppercase; letter-spacing:0.5px; }
        .summary-value { font-size:18px; font-weight:700; margin-top:2px; }

        table                { width:100%; border-collapse:collapse; }
        thead tr             { background:#2d1b47; }
        th   { padding:7px 8px; text-align:left; font-size:10px;
              font-weight:700; color:#fff; letter-spacing:0.5px;
              text-transform:uppercase; }
        td   { padding:6px 8px; border-bottom:1px solid #f3f0ff;
              font-size:11px; color:#374151; vertical-align:middle; }
        tr:nth-child(even) td { background:#faf8ff; }
        tr:last-child td      { border-bottom:none; }

      .report-footer { margin-top:16px; padding-top:8px;
                      border-top:1px solid #e5e7eb; font-size:9px;
                      color:#9ca3af; text-align:center; }
      </style>
    </head>
    <body>

      <div class="report-header">
        <div class="report-header-left">
          <div class="report-title">ONEQCU</div>
          <div class="report-subtitle">BORROW &amp; RETURN REPORT</div>
        </div>
        <div class="report-header-right">
          <div class="report-meta">
            <span>Generated:</span> ' . $generated . '<br>
            <span>Report:</span> Borrow Records Report<br>
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
            <div class="summary-label">Active</div>
            <div class="summary-value" style="color:#1d4ed8;">' . $active . '</div>
        </div>
        <div class="summary-box">
            <div class="summary-label">Overdue</div>
            <div class="summary-value" style="color:#dc2626;">' . $overdue . '</div>
        </div>
        <div class="summary-box">
            <div class="summary-label">Overdue Returns</div>
            <div class="summary-value" style="color:#f97316;">' . $overdueReturns . '</div>
        </div>
        <div class="summary-box">
            <div class="summary-label">Returned</div>
            <div class="summary-value" style="color:#15803d;">' . $returned . '</div>
        </div>
    </div>

      <table>
        <thead>
          <tr>
            <th>Request ID</th>
            <th>Borrower</th>
            <th>Department</th>
            <th>Asset ID</th>
            <th>Description</th>
            <th>Purpose</th>
            <th>Borrow Date</th>
            <th>Due Date</th>
            <th>Return Date</th>
            <th>Return Status</th> 
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
  private static function buildRows($borrows) {
    $rows = '';

    foreach ($borrows as $b) {
      $borrower = implode(' ', array_filter([
        $b['FIRST_NAME']  ?? '',
        $b['MIDDLE_NAME'] ?? '',
        $b['LAST_NAME']   ?? '',
        $b['SUFFIX']      ?? '',
      ])) ?: '—';

      $status       = $b['STATUS'] ?? '—';
      $status_color = '#374151';
      if ($status === 'Pending')   $status_color = '#b45309';
      if ($status === 'Borrowed')  $status_color = '#1d4ed8';
      if ($status === 'Overdue')   $status_color = '#dc2626';
      if ($status === 'Returned')  $status_color = '#15803d';
      if ($status === 'Cancelled') $status_color = '#6b7280';

      // Added Return Status if it is late or not
      $return_date   = $b['RETURN_DATE'] ?? '';
      $due_date      = $b['DUE_DATE']    ?? '';
      $return_status = '—';
      if ($return_date && $due_date) {
          $diff = (strtotime($return_date) - strtotime($due_date)) / 86400;
          if ($diff <= 0) {
              $return_status = 'On Time';
          } else {
              $days = ceil($diff);
              $return_status = 'Late by ' . $days . ' day(s)';
          }
      }

      $rows .= '
        <tr>
          <td>' . htmlspecialchars($b['BORROW_ID']         ?? '—') . '</td>
          <td>' . htmlspecialchars($borrower)                       . '</td>
          <td>' . htmlspecialchars($b['DEPARTMENT_NAME']   ?? '—') . '</td>
          <td>' . htmlspecialchars($b['ASSET_ID']          ?? '—') . '</td>
          <td>' . htmlspecialchars($b['ASSET_DESCRIPTION'] ?? '—') . '</td>
          <td>' . htmlspecialchars($b['PURPOSE']           ?? '—') . '</td>
          <td>' . htmlspecialchars($b['BORROW_DATE']       ?? '—') . '</td>
          <td>' . htmlspecialchars($b['DUE_DATE']          ?? '—') . '</td>
          <td>' . htmlspecialchars($return_date ?: '—')             . '</td>
          <td style="color:' . ($diff > 0 ? '#dc2626' : '#15803d') . ';font-weight:600;">'
              . htmlspecialchars($return_status)                    . '</td>
          <td style="color:' . $status_color . ';font-weight:600;">'
              . htmlspecialchars($status)                           . '</td>
        </tr>';
    }

    return $rows;
  }

}
?>