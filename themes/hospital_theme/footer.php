<?php
/* pih settings bridge */
$__pihRoot = __DIR__;
while (!file_exists($__pihRoot . '/short.php') && dirname($__pihRoot) !== $__pihRoot) {
	$__pihRoot = dirname($__pihRoot);
}
require_once $__pihRoot . '/short.php';

$url = isset($url) ? (string) $url : "";
$favicon_path = isset($favicon_path) ? (string) $favicon_path : "";
$logo_path = isset($logo_path) ? (string) $logo_path : "";
$name = isset($name) ? (string) $name : "";
$email = isset($email) ? (string) $email : "";
$phone = isset($phone) ? (string) $phone : "";
$addr = isset($addr) ? (string) $addr : "";

$__pihBase = rtrim((string) $url, '/');
$__pihThemeBase = $__pihBase !== '' ? $__pihBase . '/themes/hospital_theme' : '/themes/hospital_theme';
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
	$__pihThemeBase . '/wp-content/themes/goring/ico/favicon.png'
);
$__pihLogoUrl = pih_resolve_asset_url(
	$logo_path,
	$__pihBase,
	$__pihOrigin,
	$__pihSitePath,
	$__pihThemeBase . '/wp-content/themes/goring/images/logo.png'
);
$__pihPortalUrl = $__pihBase !== ''
	? $__pihBase . '/portal/index.php'
	: '/portal/index.php';
$__pihMap = [
	'Home - GORING MEDICAL - GORING MEDICAL' => $name . ' - Hospital Theme',
	'GORING MEDICAL' => (string) $name,
	'Goring Medical' => (string) $name,
	'info@goringmedicalprojects.com' => (string) $email,
	'+44 (0) 7584 598 649' => (string) $phone,
	'Goring Medical Projects Ltd, 1 Trinity Farm Cottages, Barnby Moor, Retford, Notts, UK, DN22 8QW' => (string) $addr,
	'© Copyright, Goring  Medical Projects Ltd, All Rights Reserved.' => '© Copyright, ' . (string) $name . ', All Rights Reserved.',
	'index.html' => 'index.php',
];

ob_start(function ($__html) use ($__pihMap, $__pihThemeBase, $__pihLogoUrl, $__pihFaviconUrl, $__pihPortalUrl) {
	$__html = str_replace(array_keys($__pihMap), array_values($__pihMap), $__html);

	// Normalize mirrored logo/favicon references to admin-configured branding URLs.
	$__html = preg_replace(
		'~(?<=[("\'=])(?:\.\./)*wp-content/themes/goring/images/logo\.png(?=["\'])~i',
		$__pihLogoUrl,
		$__html
	);
	$__html = preg_replace(
		'~(?<=[("\'=])(?:\.\./)*wp-content/themes/goring/ico/favicon\.png(?=["\'])~i',
		$__pihFaviconUrl,
		$__html
	);

	// Rebase mirrored WP asset URLs so CSS/JS/images load from this theme folder on all routes.
	$__html = preg_replace(
		'~(?<=[("\'=])(?:\.\./)*(wp-content|wp-includes|wp-json)/~i',
		$__pihThemeBase . '/$1/',
		$__html
	);

	// If logo/favicon was rebased first, force it back to admin-configured branding URLs.
	$__html = str_replace(
		[
			$__pihThemeBase . '/wp-content/themes/goring/images/logo.png',
			$__pihThemeBase . '/wp-content/themes/goring/ico/favicon.png',
		],
		[
			$__pihLogoUrl,
			$__pihFaviconUrl,
		],
		$__html
	);

	// Remove "About Us" from footer nav only.
	$__html = preg_replace_callback(
		'~(<ul[^>]*id="menu-main-menu-2"[^>]*>)(.*?)(</ul>)~is',
		static function ($m) {
			$items = preg_replace(
				'~<li\b[^>]*>\s*<a\b[^>]*href="[^"]*about-us/index\.php[^"]*"[^>]*>\s*About\s+Us\s*</a>\s*</li>~is',
				'',
				$m[2]
			);

			return $m[1] . $items . $m[3];
		},
		$__html
	);

	// Remove the entire footer "ABOUT US" social column.
	$__html = preg_replace(
		'~<div class="col-md-3 footContent">\s*<div class="footer-bio">\s*<h4>\s*ABOUT\s+US\s*</h4>.*?</div>\s*</div>\s*(?=<div class="col-md-6 footContent">)~is',
		'',
		$__html,
		1
	);

	// Rebalance remaining footer columns after About Us column removal.
	$__html = preg_replace(
		'~<div class="col-md-6 footContent">\s*<div class="row footer-bio footer-nav">~is',
		'<div class="col-md-9 footContent"><div class="row footer-bio footer-nav">',
		$__html,
		1
	);
	$__html = preg_replace(
		'~<div class="col-md-3 footContent">\s*<div class="footer-bio">\s*<h4>\s*SIGN\s+UP\s+FOR\s+NEWSLETTER\s*</h4>~is',
		'<div class="col-md-3 footContent"><div class="footer-bio"><h4>SIGN UP FOR NEWSLETTER</h4>',
		$__html,
		1
	);

	// Make the three footer link groups equally sized within the 9-column area.
	$__html = str_replace(
		'col-md-5 col-lg-5 col-sm-5 footLinks',
		'col-md-4 col-lg-4 col-sm-4 footLinks',
		$__html
	);
	$__html = str_replace(
		'col-md-3 col-lg-3 col-sm-3 footLinks',
		'col-md-4 col-lg-4 col-sm-4 footLinks',
		$__html
	);

	$__portalHref = htmlspecialchars($__pihPortalUrl, ENT_QUOTES, 'UTF-8');

	// Remove any existing menu portal injections from prior wrapper versions.
	$__html = preg_replace(
		'~<li\b[^>]*menu-item-patient-portal[^>]*>\s*<a\b[^>]*>\s*Patient\s+Portal\s*</a>\s*</li>~is',
		'',
		$__html
	);

	// Replace header Office Address block with Patient Portal button.
	$__officeReplacement = '<div class="address-box address-box-portal"><span>Patient Access</span><a class="patient-portal-top-btn btn btn-primary btn-sm" role="button" href="' . $__portalHref . '">Patient Portal</a></div>';
	$__html = preg_replace(
		'~<div class="address-box">\s*<span>\s*Office\s+Address\s*</span>\s*<p>.*?</p>\s*</div>~is',
		$__officeReplacement,
		$__html,
		1,
		$__officeCount
	);

	if ((int) $__officeCount === 0 && stripos($__html, 'patient-portal-top-btn') === false) {
		$__html = preg_replace(
			'~(<div class="top-address">)(.*?)(</div>)~is',
			'$1$2' . $__officeReplacement . '$3',
			$__html,
			1
		);
	}

	// If prior replacements create '../http://...' style paths, normalize to absolute URL.
	$__html = preg_replace(
		'~(?<=[("\'=])(?:\.\./)+(https?:)?//~i',
		'$1//',
		$__html
	);

	return $__html;
});
?>
<!-- end: Call to action -->
<!-- Footer -->
<footer id="footer">

	<div class="copyright-content">
		<div class="container">
			<a href="<?php echo $url; ?>/hipaa-notice-of-privacy-practices.php">HIPAA and Compliance</a>
			<br><a href="<?php echo $url; ?>/healthixinfo.php">Healthix</a>

			<div class="copyright-text text-center">&copy; 2025 <?php echo $name; ?>
				All Rights Reserved.</div>
		</div>
	</div>
</footer>
<!-- end: Footer -->

</div>
<!-- end: Body Inner -->

<!-- Scroll top -->
<a id="scrollTop"><i class="icon-chevron-up"></i><i class="icon-chevron-up"></i></a>
<!--Plugins-->
<script src="<?php echo $url; ?>/static/js/jquery.js"></script>
<script src="<?php echo $url; ?>/static/js/plugins.js"></script>
<script src="<?php echo $url; ?>/static/js/functions.js"></script>


<?php if (!empty($tawk)) {
	echo $tawk;
} ?>



</body>


<!-- Mirrored from <?php echo $urlh; ?>/ by HTTrack Website Copier/3.x [XR&CO'2014], Fri, 21 Feb 2025 01:10:43 GMT -->

</html>