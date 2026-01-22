<?php
// Redirect to index.html if it exists, otherwise display it
if (file_exists('index.html')) {
    readfile('index.html');
} else {
    http_response_code(404);
    echo "index.html not found";
}
?>
<?php $SEO_PAGE = 'home'; include __DIR__ . '/seo.php'; ?>
