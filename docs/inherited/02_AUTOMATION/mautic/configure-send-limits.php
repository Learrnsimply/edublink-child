<?php
// Configure Mautic email send rate limits for SAFE IP warmup
// Per Deep Research finding #2 — Gmail/Yahoo/Outlook bulk-sender rules require
// gradual ramp-up on a fresh sending IP (187.124.9.249) and domain reputation
//
// Week 1 (current, no SPF/DKIM/DMARC yet): 5/min, 50/hour, 200/day
// Week 2 (after DNS verified): increase to 200/hour, 2000/day
// Week 3+ : 500/hour, 5000/day
// Week 4+ : ready for 13K broadcast IF DNS reports clean

$file = '/var/www/html/config/local.php';
include $file;

// IP warmup — CONSERVATIVE Week 1 caps
$parameters['mailer_amount_per_minute'] = 5;
$parameters['mailer_amount_per_hour'] = 50;
$parameters['mailer_amount_per_day'] = 200;

// Email content defaults
$parameters['unsubscribe_text'] = 'لإلغاء الاشتراك، {unsubscribe_text}';
$parameters['webmaster_email'] = 'contact@learrnsimply.com';

// DSN reliability
$parameters['mailer_memory_msg_limit'] = 100;

// Tracking pixel
$parameters['anonymize_ip'] = false;

$content = "<?php\n\$parameters = " . var_export($parameters, true) . ";\n";
file_put_contents($file, $content);
echo "Email send limits applied:\n";
echo "  per_minute: 5\n";
echo "  per_hour:   50\n";
echo "  per_day:    200\n";
echo "  unsubscribe_text: Arabic\n";
echo "  webmaster:        contact@learrnsimply.com\n";
echo "Cache clear required after this\n";
