<?php
$file = '/var/www/html/config/local.php';
include $file;
$parameters['trusted_proxies'] = ['REMOTE_ADDR'];
$content = "<?php\n\$parameters = " . var_export($parameters, true) . ";\n";
file_put_contents($file, $content);
echo "local.php updated: trusted_proxies = ['REMOTE_ADDR']\n";
