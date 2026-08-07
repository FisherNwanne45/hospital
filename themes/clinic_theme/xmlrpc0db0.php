<?php
/* pih settings bridge */
$__pihRoot = __DIR__;
while (!file_exists($__pihRoot . '/short.php') && dirname($__pihRoot) !== $__pihRoot) {
	$__pihRoot = dirname($__pihRoot);
}
require_once $__pihRoot . '/short.php';

$url = isset($url) ? (string) $url : '';
$favicon_path = isset($favicon_path) ? (string) $favicon_path : '';
$logo_path = isset($logo_path) ? (string) $logo_path : '';
$name = isset($name) ? (string) $name : '';
$email = isset($email) ? (string) $email : '';
$phone = isset($phone) ? (string) $phone : '';

$__pihBase = rtrim((string) $url, '/');
$__pihThemeBase = $__pihBase !== '' ? $__pihBase . '/themes/clinic_theme' : '/themes/clinic_theme';
$__pihSitePath = (string) parse_url($__pihBase, PHP_URL_PATH);
$__pihSitePath = $__pihSitePath !== '' ? rtrim($__pihSitePath, '/') : '';
$__pihScheme = (string) parse_url($__pihBase, PHP_URL_SCHEME);
$__pihHost = (string) parse_url($__pihBase, PHP_URL_HOST);
$__pihPort = (string) parse_url($__pihBase, PHP_URL_PORT);
$__pihOrigin = ($__pihScheme !== '' && $__pihHost !== '')
	? $__pihScheme . '://' . $__pihHost . ($__pihPort !== '' ? ':' . $__pihPort : '')
	: '';

if (!function_exists('pih_resolve_asset_url')) {
	function pih_resolve_asset_url($path, $base, $origin, $sitePath, $fallback)
	{
		$path = trim((string) $path);
		if ($path === '') {
			return $fallback;
		}
		if (preg_match('~^(?:https?:)?//~i', $path)) {
			return $path;
		}
		if ($base !== '' && str_starts_with($path, $base . '/')) {
			return $path;
		}
		if ($sitePath !== '' && str_starts_with($path, $sitePath . '/') && $origin !== '') {
			return $origin . $path;
		}
		if (str_starts_with($path, '/')) {
			if ($origin !== '') {
				return $origin . $path;
			}
			return $base !== '' ? rtrim($base, '/') . $path : $path;
		}
		return ($base !== '' ? rtrim($base, '/') : '') . '/' . ltrim($path, '/');
	}
}

$__pihFaviconUrl = pih_resolve_asset_url(
	$favicon_path,
	$__pihBase,
	$__pihOrigin,
	$__pihSitePath,
	$__pihThemeBase . '/wp-content/uploads/2026/02/circleonlyahi-logo-150x150.jpg'
);
$__pihLogoUrl = pih_resolve_asset_url(
	$logo_path,
	$__pihBase,
	$__pihOrigin,
	$__pihSitePath,
	$__pihThemeBase . '/wp-content/uploads/2026/02/cropped-ahi-logo-363x91.jpeg'
);

$__pihMap = [
	'Advanced Health Imaging, LLC' => (string) $name,
	'Advanced Health Imaging' => (string) $name,
	'|  MRI   CT   XRAY   ULTRASOUND - ' => (string) $name,
	'index.html' => 'index.php',
];

ob_start(function ($__html) use ($__pihMap, $__pihThemeBase, $__pihLogoUrl, $__pihFaviconUrl, $__pihBase, $name) {
	$__html = str_replace(array_keys($__pihMap), array_values($__pihMap), $__html);

	// Rebase WP-style asset paths to theme-local mirror for nested routes.
	$__html = preg_replace(
		'~(?<=[("\'=])(?:\.\./)*(wp-content|wp-includes|wp-json)/~i',
		$__pihThemeBase . '/$1/',
		$__html
	);

	// Normalize logo/favicon sources to configured branding paths.
	$__html = preg_replace(
		'~(?<=[("\'=])[^"\']*cropped-ahi-logo[^"\']*(?=["\'])~i',
		$__pihLogoUrl,
		$__html
	);
	$__html = preg_replace(
		'~(?<=[("\'=])[^"\']*circleonlyahi-logo[^"\']*(?=["\'])~i',
		$__pihFaviconUrl,
		$__html
	);

	// Rewrite local .html references to .php at render time as a safety net.
	$__html = preg_replace(
		'~(?<=[("\'=])(?!https?:|//|mailto:|tel:|#)([^"\'\s\?#]+?)\.html(?=(?:[?#][^"\']*)?["\'])~i',
		'$1.php',
		$__html
	);

	if ($__pihBase !== '') {
		$__html = preg_replace(
			'~(?<=[("\'=])/+(about|services|resources|contact|feed|comments|wp-json|wp-admin)/~i',
			$__pihBase . '/$1/',
			$__html
		);
		$__html = str_replace('href="index.php"', 'href="' . $__pihBase . '/index.php"', $__html);
	}

	// Remove dead mirrored feed/xmlrpc links that resolve to local 404s.
	$__html = preg_replace(
		'~<link\b[^>]*href=["\"][^"\"]*(?:comments/feed/index\.php|(?:^|/)feed/index\.php|xmlrpc[0-9a-f]*\.php\?rsd)[^"\"]*["\"][^>]*>\s*~i',
		'',
		$__html
	);
	$__html = preg_replace('~<link\b[^>]*type=["\"]application/rss\+xml["\"][^>]*>\s*~i', '', $__html);

	// Rewrite legacy WP shortlinks to local routed pages.
	$__html = preg_replace_callback(
		'~(?<=[("\'=])(?:\.\./)*index[0-9a-f]{4}\.php\?p=(\d+)(?=["\"])~i',
		static function (array $m) use ($__pihBase): string {
			$map = [
				'298' => '/about/index.php',
				'299' => '/services/index.php',
				'301' => '/contact/index.php',
				'589' => '/resources/index.php',
			];

			$pageId = $m[1] ?? '';
			if (!isset($map[$pageId])) {
				return $m[0];
			}

			$base = rtrim((string) $__pihBase, '/');
			return ($base !== '' ? $base : '') . $map[$pageId];
		},
		$__html
	);

	$__base = rtrim((string) $__pihBase, '/');
	$__legacyShortlinks = [
		'../indexba86.php?p=298' => ($__base !== '' ? $__base : '') . '/about/index.php',
		'indexba86.php?p=298' => ($__base !== '' ? $__base : '') . '/about/index.php',
		'../indexbc9e.php?p=299' => ($__base !== '' ? $__base : '') . '/services/index.php',
		'indexbc9e.php?p=299' => ($__base !== '' ? $__base : '') . '/services/index.php',
		'../index8a7d.php?p=301' => ($__base !== '' ? $__base : '') . '/contact/index.php',
		'index8a7d.php?p=301' => ($__base !== '' ? $__base : '') . '/contact/index.php',
		'../index4f61.php?p=589' => ($__base !== '' ? $__base : '') . '/resources/index.php',
		'index4f61.php?p=589' => ($__base !== '' ? $__base : '') . '/resources/index.php',
	];
	$__html = str_replace(array_keys($__legacyShortlinks), array_values($__legacyShortlinks), $__html);

	$__buttonOverrideCss = '<style id="pih-clinic-button-overrides">'
		. '.ast-header-button-1 .ast-custom-button,.ast-header-button-1 .ast-custom-button-link,.uagb-infobox-cta-link,.uagb-buttons-repeater.wp-block-button__link,.wp-block-uagb-buttons .uagb-buttons-repeater.wp-block-button__link,.srfm-submit-button{background-color:var(--ast-global-color-0)!important;border-color:var(--ast-global-color-0)!important;color:#ffffff!important;}'
		. '.home .uagb-infobox-cta-link,.home .uagb-buttons-repeater.wp-block-button__link,.home .wp-block-uagb-buttons .uagb-buttons-repeater.wp-block-button__link{border-style:solid!important;border-width:2px!important;border-radius:10px!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;padding:12px 22px!important;min-height:44px!important;line-height:1.2!important;font-weight:600!important;text-decoration:none!important;transition:all .2s ease!important;}'
		. '.home .uagb-buttons-repeater.wp-block-button__link .uagb-button__link,.home .wp-block-uagb-buttons .uagb-buttons-repeater.wp-block-button__link .uagb-button__link{padding:0!important;margin:0!important;line-height:1.2!important;font-weight:600!important;}'
		. '.home .uagb-infobox-cta-link .uagb-inline-editing{line-height:1.2!important;font-weight:600!important;}'
		. '.home .uagb-block-2c16c927 .uagb-button__wrapper,.home .uagb-block-591505c6 .uagb-button__wrapper,.home .uagb-block-416130fd .uagb-button__wrapper,.home .uagb-block-ad6b3c6f .uagb-button__wrapper{width:100%!important;}'
		. '.home .uagb-block-2c16c927 .uagb-buttons-repeater.wp-block-button__link,.home .uagb-block-591505c6 .uagb-buttons-repeater.wp-block-button__link,.home .uagb-block-416130fd .uagb-buttons-repeater.wp-block-button__link,.home .uagb-block-ad6b3c6f .uagb-buttons-repeater.wp-block-button__link{width:100%!important;max-width:100%!important;white-space:nowrap!important;flex-wrap:nowrap!important;}'
		. '.home .uagb-block-2c16c927 .uagb-button__link,.home .uagb-block-591505c6 .uagb-button__link,.home .uagb-block-416130fd .uagb-button__link,.home .uagb-block-ad6b3c6f .uagb-button__link{white-space:nowrap!important;}'
		. '.uagb-buttons-repeater.wp-block-button__link .uagb-button__link,.wp-block-uagb-buttons .uagb-buttons-repeater.wp-block-button__link .uagb-button__link{color:#ffffff!important;}'
		. '.ast-header-button-1 .ast-custom-button-link:hover .ast-custom-button,.ast-header-button-1 .ast-custom-button-link:focus .ast-custom-button,.ast-header-button-1 .ast-custom-button-link:focus-visible .ast-custom-button,.ast-header-button-1 .ast-custom-button-link:active .ast-custom-button,.ast-header-button-1 .ast-custom-button-link:hover,.ast-header-button-1 .ast-custom-button-link:focus,.ast-header-button-1 .ast-custom-button-link:focus-visible,.ast-header-button-1 .ast-custom-button-link:active,.uagb-infobox-cta-link:hover,.uagb-infobox-cta-link:focus,.uagb-infobox-cta-link:focus-visible,.uagb-infobox-cta-link:active,.uagb-buttons-repeater.wp-block-button__link:hover,.uagb-buttons-repeater.wp-block-button__link:focus,.uagb-buttons-repeater.wp-block-button__link:focus-visible,.uagb-buttons-repeater.wp-block-button__link:active,.wp-block-uagb-buttons .uagb-buttons-repeater.wp-block-button__link:hover,.wp-block-uagb-buttons .uagb-buttons-repeater.wp-block-button__link:focus,.wp-block-uagb-buttons .uagb-buttons-repeater.wp-block-button__link:focus-visible,.wp-block-uagb-buttons .uagb-buttons-repeater.wp-block-button__link:active,.srfm-submit-button:hover,.srfm-submit-button:focus,.srfm-submit-button:focus-visible,.srfm-submit-button:active{background-color:#ffffff!important;border-color:var(--ast-global-color-0)!important;color:var(--ast-global-color-0)!important;}'
		. '.uagb-buttons-repeater.wp-block-button__link:hover .uagb-button__link,.uagb-buttons-repeater.wp-block-button__link:focus .uagb-button__link,.uagb-buttons-repeater.wp-block-button__link:focus-visible .uagb-button__link,.uagb-buttons-repeater.wp-block-button__link:active .uagb-button__link,.wp-block-uagb-buttons .uagb-buttons-repeater.wp-block-button__link:hover .uagb-button__link,.wp-block-uagb-buttons .uagb-buttons-repeater.wp-block-button__link:focus .uagb-button__link,.wp-block-uagb-buttons .uagb-buttons-repeater.wp-block-button__link:focus-visible .uagb-button__link,.wp-block-uagb-buttons .uagb-buttons-repeater.wp-block-button__link:active .uagb-button__link{color:var(--ast-global-color-0)!important;}'
		. '#ast-scroll-top{background-color:var(--ast-global-color-0)!important;border:1px solid var(--ast-global-color-0)!important;color:#ffffff!important;}'
		. '#ast-scroll-top .ast-icon.icon-arrow svg{fill:#ffffff!important;}'
		. '#ast-scroll-top:hover,#ast-scroll-top:focus,#ast-scroll-top:focus-visible,#ast-scroll-top:active{background-color:#ffffff!important;border-color:var(--ast-global-color-0)!important;color:var(--ast-global-color-0)!important;}'
		. '#ast-scroll-top:hover .ast-icon.icon-arrow svg,#ast-scroll-top:focus .ast-icon.icon-arrow svg,#ast-scroll-top:focus-visible .ast-icon.icon-arrow svg,#ast-scroll-top:active .ast-icon.icon-arrow svg{fill:var(--ast-global-color-0)!important;}'
		. '</style>';
	if (stripos($__html, 'id="pih-clinic-button-overrides"') === false) {
		$__html = preg_replace('~</head>~i', $__buttonOverrideCss . '</head>', $__html, 1);
	}

	// Keep title non-empty after replacements.
	if ($name !== '') {
		$__html = preg_replace('~<title>\s*</title>~i', '<title>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</title>', $__html, 1);
	}

	// Normalize accidental relative prefix before absolute URL.
	$__html = preg_replace('~(?<=[("\'=])(?:\.\./)+(https?:)?//~i', '$1//', $__html);

	return $__html;
});
?>
<script>document.cookie = "humans_21909=1"; document.location.reload(true)</script>