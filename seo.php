<?php
// TrackBack/seo.php
// Usage: set $SEO_PAGE = 'home' (or login/registration) then include this file inside <head>.

$BASE_URL = 'https://trackback.top';

function current_url($base) {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
  $host = $_SERVER['HTTP_HOST'] ?? parse_url($base, PHP_URL_HOST);
  $uri  = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
  return $scheme . "://" . $host . $uri;
}

$pages = [
  'home' => [
    'title' => 'TrackBack — Student Lost & Found Platform',
    'desc'  => 'TrackBack helps students quickly report, find, and return lost items on campus. Simple posting, verified updates, and fast communication.',
    'path'  => '/',
    'image' => $BASE_URL . '/img/og/og-default.jpg'
  ],
  'login' => [
    'title' => 'Login — TrackBack',
    'desc'  => 'Log in to TrackBack to access the Lost & Found dashboard and manage your reports.',
    'path'  => '/login.php',
    'image' => $BASE_URL . '/img/og/og-default.jpg'
  ],
  'registration' => [
    'title' => 'Sign Up — TrackBack',
    'desc'  => 'Create a TrackBack account to post lost or found items and connect with other students.',
    'path'  => '/registration.php',
    'image' => $BASE_URL . '/img/og/og-default.jpg'
  ],
];

$key = isset($SEO_PAGE, $pages[$SEO_PAGE]) ? $SEO_PAGE : 'home';
$seo = $pages[$key];

$canonical = $BASE_URL . $seo['path'];
$url = current_url($BASE_URL);
$title = $seo['title'];
$desc = $seo['desc'];
$img = $seo['image'];
?>
<title><?= htmlspecialchars($title) ?></title>

<meta name="description" content="<?= htmlspecialchars($desc) ?>" />
<meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1" />
<meta name="googlebot" content="index,follow" />
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>" />

<!-- Open Graph -->
<meta property="og:type" content="website" />
<meta property="og:site_name" content="TrackBack" />
<meta property="og:title" content="<?= htmlspecialchars($title) ?>" />
<meta property="og:description" content="<?= htmlspecialchars($desc) ?>" />
<meta property="og:url" content="<?= htmlspecialchars($url) ?>" />
<meta property="og:image" content="<?= htmlspecialchars($img) ?>" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:locale" content="en_US" />

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="<?= htmlspecialchars($title) ?>" />
<meta name="twitter:description" content="<?= htmlspecialchars($desc) ?>" />
<meta name="twitter:image" content="<?= htmlspecialchars($img) ?>" />

<!-- Optional: theme color -->
<meta name="theme-color" content="#0B57D0" />

<!-- Schema.org JSON-LD -->
<script type="application/ld+json">
<?= json_encode([
  "@context" => "https://schema.org",
  "@graph" => [
    [
      "@type" => "Organization",
      "@id" => $BASE_URL . "/#organization",
      "name" => "TrackBack",
      "url" => $BASE_URL,
      "logo" => [
        "@type" => "ImageObject",
        "url" => $BASE_URL . "/img/logo/logo-2.png"
      ]
    ],
    [
      "@type" => "WebSite",
      "@id" => $BASE_URL . "/#website",
      "url" => $BASE_URL,
      "name" => "TrackBack",
      "publisher" => ["@id" => $BASE_URL . "/#organization"],
      "inLanguage" => "en"
    ],
    [
      "@type" => "WebPage",
      "@id" => $canonical . "#webpage",
      "url" => $canonical,
      "name" => $title,
      "description" => $desc,
      "isPartOf" => ["@id" => $BASE_URL . "/#website"],
      "about" => ["@id" => $BASE_URL . "/#organization"],
      "inLanguage" => "en"
    ]
  ]
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
