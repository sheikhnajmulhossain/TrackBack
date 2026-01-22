<?php
$BASE_URL = 'https://trackback.top';

function current_url($base) {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
  $host = $_SERVER['HTTP_HOST'] ?? parse_url($base, PHP_URL_HOST);
  $uri  = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
  return $scheme . "://" . $host . $uri;
}

// Public pages only
$pages = [
  'home' => [
    'title' => 'TrackBack — Student Lost & Found Platform',
    'desc'  => 'TrackBack helps students quickly report, find, and return lost items on campus. Simple posting, verified updates, and fast communication.',
    'path'  => '/TrackBack/',
    'image' => $BASE_URL . '/TrackBack/img/og/og-default.png'
  ],
];

$key = isset($SEO_PAGE, $pages[$SEO_PAGE]) ? $SEO_PAGE : 'home';
$seo = $pages[$key];

$canonical = $BASE_URL . $seo['path'];
$title = $seo['title'];
$desc  = $seo['desc'];
$img   = $seo['image'];
?>
<title><?= htmlspecialchars($title) ?></title>

<meta name="description" content="<?= htmlspecialchars($desc) ?>" />
<meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1" />
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>" />

<!-- Open Graph -->
<meta property="og:type" content="website" />
<meta property="og:site_name" content="TrackBack" />
<meta property="og:title" content="<?= htmlspecialchars($title) ?>" />
<meta property="og:description" content="<?= htmlspecialchars($desc) ?>" />
<meta property="og:url" content="<?= htmlspecialchars($canonical) ?>" />
<meta property="og:image" content="<?= htmlspecialchars($img) ?>" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="<?= htmlspecialchars($title) ?>" />
<meta name="twitter:description" content="<?= htmlspecialchars($desc) ?>" />
<meta name="twitter:image" content="<?= htmlspecialchars($img) ?>" />

<!-- Schema -->
<script type="application/ld+json">
<?= json_encode([
  "@context" => "https://schema.org",
  "@type" => "WebSite",
  "url" => $BASE_URL . "/TrackBack/",
  "name" => "TrackBack",
  "publisher" => [
    "@type" => "Organization",
    "name" => "TrackBack",
    "url" => $BASE_URL,
    "logo" => [
      "@type" => "ImageObject",
      "url" => $BASE_URL . "/TrackBack/img/og/og-default.png"
    ]
  ]
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
