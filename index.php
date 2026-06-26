<?php
// Consider installed if DB_HOST env var is set (Render) OR .env file exists
$isInstalled = !empty(getenv('DB_HOST')) || file_exists(__DIR__ . '/.env');
if (!$isInstalled) {
    header('Location: install.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="favicon.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="index, follow" />
    <meta name="description" content="HSI is a non-profit strengthening health systems through effective, equitable, and sustainable digital technologies — improving coordination, communication, and access to quality healthcare." />
    <meta name="keywords" content="health systems strengthening, digital health Africa, healthcare coordination Nigeria, health equity, NICU Network" />
    <title>Health Systems Initiative | Building Health Systems. Saving Lives.</title>

    <!-- Canonical (updated per-page by usePageMeta) -->
    <link rel="canonical" href="https://healthsystemsinitiative.org/" />

    <!-- Open Graph -->
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Health Systems Initiative" />
    <meta property="og:title" content="Health Systems Initiative | Building Health Systems. Saving Lives." />
    <meta property="og:description" content="HSI is a non-profit strengthening health systems through effective, equitable, and sustainable digital technologies." />
    <meta property="og:image" content="https://healthsystemsinitiative.org/og-image.png" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />
    <meta property="og:url" content="https://healthsystemsinitiative.org" />
    <meta property="og:locale" content="en_NG" />

    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:site" content="@HSINigeria" />
    <meta name="twitter:title" content="Health Systems Initiative" />
    <meta name="twitter:description" content="Building health systems. Saving lives. Digital technologies for equitable healthcare in Africa." />
    <meta name="twitter:image" content="https://healthsystemsinitiative.org/og-image.png" />

    <!-- Sitemap reference -->
    <link rel="sitemap" type="application/xml" href="data:application/octet-stream;base64,PD9waHAKcmVxdWlyZV9vbmNlIF9fRElSX18gLiAnL2NvbmZpZy9kYXRhYmFzZS5waHAnOwoKaGVhZGVyKCdDb250ZW50LVR5cGU6IGFwcGxpY2F0aW9uL3htbDsgY2hhcnNldD1VVEYtOCcpOwpoZWFkZXIoJ1gtUm9ib3RzLVRhZzogbm9pbmRleCcpOwoKdHJ5IHsKICAgICRkYiA9IGdldERCKCk7CiAgICAkc3RtdCA9ICRkYi0+cXVlcnkoIlNFTEVDVCB2YWx1ZSBGUk9NIHNpdGVfc2V0dGluZ3MgV0hFUkUgc2V0dGluZ19rZXkgPSAnc2l0ZV91cmwnIik7CiAgICAkcm93ICA9ICRzdG10LT5mZXRjaCgpOwogICAgJGJhc2UgPSBydHJpbSgkcm93Wyd2YWx1ZSddID8/ICdodHRwczovL2hlYWx0aHN5c3RlbXNpbml0aWF0aXZlLm9yZycsICcvJyk7Cn0gY2F0Y2ggKEV4Y2VwdGlvbiAkZSkgewogICAgJGJhc2UgPSAnaHR0cHM6Ly9oZWFsdGhzeXN0ZW1zaW5pdGlhdGl2ZS5vcmcnOwp9Cgokbm93ID0gZGF0ZSgnWS1tLWQnKTsKCiRzdGF0aWMgPSBbCiAgICBbJ2xvYycgPT4gJGJhc2UgLiAnLycsICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAncHJpb3JpdHknID0+ICcxLjAnLCAnZnJlcScgPT4gJ3dlZWtseSddLAogICAgWydsb2MnID0+ICRiYXNlIC4gJy8jL2Fib3V0JywgICAgICAgICAgICAgICAgICAgICAgICAgJ3ByaW9yaXR5JyA9PiAnMC44JywgJ2ZyZXEnID0+ICdtb250aGx5J10sCiAgICBbJ2xvYycgPT4gJGJhc2UgLiAnLyMvb3VyLWFwcHJvYWNoJywgICAgICAgICAgICAgICAgICAncHJpb3JpdHknID0+ICcwLjgnLCAnZnJlcScgPT4gJ21vbnRobHknXSwKICAgIFsnbG9jJyA9PiAkYmFzZSAuICcvIy9wcm9qZWN0cycsICAgICAgICAgICAgICAgICAgICAgICdwcmlvcml0eScgPT4gJzAuOScsICdmcmVxJyA9PiAnd2Vla2x5J10sCiAgICBbJ2xvYycgPT4gJGJhc2UgLiAnLyMvcHJvamVjdHMvbmljdS1uZXR3b3JrLW5pZ2VyaWEnLCAncHJpb3JpdHknID0+ICcwLjknLCAnZnJlcScgPT4gJ21vbnRobHknXSwKICAgIFsnbG9jJyA9PiAkYmFzZSAuICcvIy90ZWFtJywgICAgICAgICAgICAgICAgICAgICAgICAgICdwcmlvcml0eScgPT4gJzAuNycsICdmcmVxJyA9PiAnbW9udGhseSddLAogICAgWydsb2MnID0+ICRiYXNlIC4gJy8jL25ld3MnLCAgICAgICAgICAgICAgICAgICAgICAgICAgJ3ByaW9yaXR5JyA9PiAnMC44JywgJ2ZyZXEnID0+ICdkYWlseSddLAogICAgWydsb2MnID0+ICRiYXNlIC4gJy8jL2dldC1pbnZvbHZlZCcsICAgICAgICAgICAgICAgICAgJ3ByaW9yaXR5JyA9PiAnMC45JywgJ2ZyZXEnID0+ICdtb250aGx5J10sCiAgICBbJ2xvYycgPT4gJGJhc2UgLiAnLyMvY29udGFjdCcsICAgICAgICAgICAgICAgICAgICAgICAncHJpb3JpdHknID0+ICcwLjcnLCAnZnJlcScgPT4gJ3llYXJseSddLApdOwoKZWNobyAnPD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4nIC4gIlxuIjsKZWNobyAnPHVybHNldCB4bWxucz0iaHR0cDovL3d3dy5zaXRlbWFwcy5vcmcvc2NoZW1hcy9zaXRlbWFwLzAuOSI+JyAuICJcbiI7Cgpmb3JlYWNoICgkc3RhdGljIGFzICR1cmwpIHsKICAgIGVjaG8gIiAgPHVybD5cbiI7CiAgICBlY2hvICIgICAgPGxvYz4iIC4gaHRtbHNwZWNpYWxjaGFycygkdXJsWydsb2MnXSkgLiAiPC9sb2M+XG4iOwogICAgZWNobyAiICAgIDxsYXN0bW9kPnskbm93fTwvbGFzdG1vZD5cbiI7CiAgICBlY2hvICIgICAgPGNoYW5nZWZyZXE+eyR1cmxbJ2ZyZXEnXX08L2NoYW5nZWZyZXE+XG4iOwogICAgZWNobyAiICAgIDxwcmlvcml0eT57JHVybFsncHJpb3JpdHknXX08L3ByaW9yaXR5PlxuIjsKICAgIGVjaG8gIiAgPC91cmw+XG4iOwp9CgplY2hvICc8L3VybHNldD4nOwo=" />

    <!-- Organisation JSON-LD (site-wide, stable) -->
    <script type="application/ld+json" id="org-jsonld">
    {
      "@context": "https://schema.org",
      "@type": "NGO",
      "name": "Health Systems Initiative",
      "alternateName": "HSI",
      "url": "https://healthsystemsinitiative.org",
      "logo": {
        "@type": "ImageObject",
        "url": "https://healthsystemsinitiative.org/favicon.svg",
        "width": 512,
        "height": 512
      },
      "description": "A non-profit organization strengthening health systems through effective, equitable, and sustainable digital technologies in Nigeria and across Africa.",
      "foundingDate": "2022",
      "areaServed": { "@type": "Place", "name": "Africa" },
      "knowsAbout": ["Digital Health", "Health Systems Strengthening", "Neonatal Care", "Health Equity"],
      "sameAs": [
        "https://twitter.com/HSINigeria",
        "https://www.linkedin.com/company/health-systems-initiative"
      ],
      "address": {
        "@type": "PostalAddress",
        "addressCountry": "NG",
        "addressRegion": "Lagos"
      },
      "contactPoint": {
        "@type": "ContactPoint",
        "contactType": "General Enquiries",
        "email": "info@healthsystemsinitiative.org"
      }
    }
    </script>

    <!-- WebSite JSON-LD for Sitelinks Searchbox eligibility -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Health Systems Initiative",
      "url": "https://healthsystemsinitiative.org"
    }
    </script>

    <!-- Fonts: preconnect + non-blocking load -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Merriweather:wght@400;700&display=swap" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Merriweather:wght@400;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'" />
    <noscript>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Merriweather:wght@400;700&display=swap" rel="stylesheet" />
    </noscript>
    <script type="module" crossorigin src="./assets/index--1mpHLIM.js"></script>
    <link rel="stylesheet" crossorigin href="./assets/index-0klAM21w.css">
  </head>
  <body>
    <div id="root"></div>
  </body>
</html>
