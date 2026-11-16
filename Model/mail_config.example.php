<?php

define('SMTP_HOST', getenv('TRACKBACK_SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int) (getenv('TRACKBACK_SMTP_PORT') ?: 587));
define('SMTP_SECURE', getenv('TRACKBACK_SMTP_SECURE') ?: 'tls');
define('SMTP_USERNAME', getenv('TRACKBACK_SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('TRACKBACK_SMTP_PASSWORD') ?: '');
define('MAIL_FROM', getenv('TRACKBACK_MAIL_FROM') ?: SMTP_USERNAME);
define('MAIL_FROM_NAME', getenv('TRACKBACK_MAIL_FROM_NAME') ?: 'TrackBack');
