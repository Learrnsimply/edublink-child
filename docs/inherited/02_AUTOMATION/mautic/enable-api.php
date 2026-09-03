<?php
$file = '/var/www/html/config/local.php';
include $file;

$parameters['api_enabled'] = true;
$parameters['api_enable_basic_auth'] = true;
$parameters['api_batch_max_limit'] = 200;

$content = "<?php\n\$parameters = " . var_export($parameters, true) . ";\n";
file_put_contents($file, $content);
echo "API enabled: api_enabled=true, basic_auth=true, batch_max=200\n";
