<?php
// Visit: http://localhost/1QCUPROJECT/check_jgraph.php
echo extension_loaded('gd') ? 'GD is enabled' : 'GD is NOT enabled';
echo '<br>';
echo extension_loaded('gd') ? json_encode(gd_info()) : '';
echo '<br>';
echo '<br>';

// CHECKING THE JPGraph Version
define('JPGRAPH_DIR', 'C:/xampp/htdocs/1QCUPROJECT/vendor/jpgraph-4.4.3/src/');
require_once JPGRAPH_DIR . 'jpgraph.php';
echo 'JPGraph loaded successfully — Version: ' . JPG_VERSION;
?>