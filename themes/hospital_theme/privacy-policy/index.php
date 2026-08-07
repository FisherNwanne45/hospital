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
<!DOCTYPE html>
<html lang="en-US" prefix="og: https://ogp.me/ns#">

<!-- Mirrored from goringmedicalprojects.com/privacy-policy/ by HTTrack Website Copier/3.x [XR&CO'2014], Thu, 06 Aug 2026 14:39:41 GMT -->
<!-- Added by HTTrack -->
<meta http-equiv="content-type" content="text/html;charset=UTF-8" /><!-- /Added by HTTrack -->

<head>
	<!-- Basic Page Needs
================================================== -->
	<meta name="robots" content="follow, index" />
	<meta charset="UTF-8" />
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="">
	<meta name="author" content="">
	<link rel="shortcut icon" href="../wp-content/themes/goring/ico/favicon.png">
	<title>
		Privacy Policy - GORING MEDICAL - GORING MEDICAL </title>
	<!-- Bootstrap core CSS -->
	<link href="../wp-content/themes/goring/css/bootstrap.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="../wp-content/themes/goring/style.css">
	<link rel="stylesheet" type="text/css" href="../wp-content/themes/goring/css/animate.css">
	<link rel="stylesheet" type="text/css" href="../wp-content/themes/goring/slick/slick.css">
	<link rel="stylesheet" type="text/css" href="../wp-content/themes/goring/slick/slick-theme.css">
	<!-- Owl Carousel Assets -->
	<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">
	<style>
		img:is([sizes="auto" i], [sizes^="auto," i]) {
			contain-intrinsic-size: 3000px 1500px
		}
	</style>

	<!-- Search Engine Optimization by Rank Math - https://s.rankmath.com/home -->
	<meta name="description" content="Your privacy is extremely important to us. It is Goring Medical Ltd’s policy to respect your privacy regarding any information we may collect from you across" />
	<meta name="robots" content="index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
	<link rel="canonical" href="index.php" />
	<meta property="og:locale" content="en_US" />
	<meta property="og:type" content="article" />
	<meta property="og:title" content="Privacy Policy - GORING MEDICAL" />
	<meta property="og:description" content="Your privacy is extremely important to us. It is Goring Medical Ltd’s policy to respect your privacy regarding any information we may collect from you across" />
	<meta property="og:url" content="index.php" />
	<meta property="og:site_name" content="GORING MEDICAL" />
	<meta property="og:updated_time" content="2020-10-26T08:17:05+00:00" />
	<meta property="og:image" content="../wp-content/uploads/2019/02/Banner_Image.png" />
	<meta property="og:image:secure_url" content="../wp-content/uploads/2019/02/Banner_Image.png" />
	<meta property="og:image:width" content="1600" />
	<meta property="og:image:height" content="850" />
	<meta property="og:image:alt" content="Privacy Policy" />
	<meta property="og:image:type" content="image/png" />
	<meta name="twitter:card" content="summary_large_image" />
	<meta name="twitter:title" content="Privacy Policy - GORING MEDICAL" />
	<meta name="twitter:description" content="Your privacy is extremely important to us. It is Goring Medical Ltd’s policy to respect your privacy regarding any information we may collect from you across" />
	<meta name="twitter:image" content="../wp-content/uploads/2019/02/Banner_Image.png" />
	<!-- /Rank Math WordPress SEO plugin -->

	<script type="text/javascript">
		/* <![CDATA[ */
		window._wpemojiSettings = {
			"baseUrl": "https:\/\/s.w.org\/images\/core\/emoji\/15.0.3\/72x72\/",
			"ext": ".png",
			"svgUrl": "https:\/\/s.w.org\/images\/core\/emoji\/15.0.3\/svg\/",
			"svgExt": ".svg",
			"source": {
				"concatemoji": "https:\/\/goringmedicalprojects.com\/wp-includes\/js\/wp-emoji-release.min.js?ver=6.7.5"
			}
		};
		/*! This file is auto-generated */
		! function(i, n) {
			var o, s, e;

			function c(e) {
				try {
					var t = {
						supportTests: e,
						timestamp: (new Date).valueOf()
					};
					sessionStorage.setItem(o, JSON.stringify(t))
				} catch (e) {}
			}

			function p(e, t, n) {
				e.clearRect(0, 0, e.canvas.width, e.canvas.height), e.fillText(t, 0, 0);
				var t = new Uint32Array(e.getImageData(0, 0, e.canvas.width, e.canvas.height).data),
					r = (e.clearRect(0, 0, e.canvas.width, e.canvas.height), e.fillText(n, 0, 0), new Uint32Array(e.getImageData(0, 0, e.canvas.width, e.canvas.height).data));
				return t.every(function(e, t) {
					return e === r[t]
				})
			}

			function u(e, t, n) {
				switch (t) {
					case "flag":
						return n(e, "\ud83c\udff3\ufe0f\u200d\u26a7\ufe0f", "\ud83c\udff3\ufe0f\u200b\u26a7\ufe0f") ? !1 : !n(e, "\ud83c\uddfa\ud83c\uddf3", "\ud83c\uddfa\u200b\ud83c\uddf3") && !n(e, "\ud83c\udff4\udb40\udc67\udb40\udc62\udb40\udc65\udb40\udc6e\udb40\udc67\udb40\udc7f", "\ud83c\udff4\u200b\udb40\udc67\u200b\udb40\udc62\u200b\udb40\udc65\u200b\udb40\udc6e\u200b\udb40\udc67\u200b\udb40\udc7f");
					case "emoji":
						return !n(e, "\ud83d\udc26\u200d\u2b1b", "\ud83d\udc26\u200b\u2b1b")
				}
				return !1
			}

			function f(e, t, n) {
				var r = "undefined" != typeof WorkerGlobalScope && self instanceof WorkerGlobalScope ? new OffscreenCanvas(300, 150) : i.createElement("canvas"),
					a = r.getContext("2d", {
						willReadFrequently: !0
					}),
					o = (a.textBaseline = "top", a.font = "600 32px Arial", {});
				return e.forEach(function(e) {
					o[e] = t(a, e, n)
				}), o
			}

			function t(e) {
				var t = i.createElement("script");
				t.src = e, t.defer = !0, i.head.appendChild(t)
			}
			"undefined" != typeof Promise && (o = "wpEmojiSettingsSupports", s = ["flag", "emoji"], n.supports = {
				everything: !0,
				everythingExceptFlag: !0
			}, e = new Promise(function(e) {
				i.addEventListener("DOMContentLoaded", e, {
					once: !0
				})
			}), new Promise(function(t) {
				var n = function() {
					try {
						var e = JSON.parse(sessionStorage.getItem(o));
						if ("object" == typeof e && "number" == typeof e.timestamp && (new Date).valueOf() < e.timestamp + 604800 && "object" == typeof e.supportTests) return e.supportTests
					} catch (e) {}
					return null
				}();
				if (!n) {
					if ("undefined" != typeof Worker && "undefined" != typeof OffscreenCanvas && "undefined" != typeof URL && URL.createObjectURL && "undefined" != typeof Blob) try {
						var e = "postMessage(" + f.toString() + "(" + [JSON.stringify(s), u.toString(), p.toString()].join(",") + "));",
							r = new Blob([e], {
								type: "text/javascript"
							}),
							a = new Worker(URL.createObjectURL(r), {
								name: "wpTestEmojiSupports"
							});
						return void(a.onmessage = function(e) {
							c(n = e.data), a.terminate(), t(n)
						})
					} catch (e) {}
					c(n = f(s, u, p))
				}
				t(n)
			}).then(function(e) {
				for (var t in e) n.supports[t] = e[t], n.supports.everything = n.supports.everything && n.supports[t], "flag" !== t && (n.supports.everythingExceptFlag = n.supports.everythingExceptFlag && n.supports[t]);
				n.supports.everythingExceptFlag = n.supports.everythingExceptFlag && !n.supports.flag, n.DOMReady = !1, n.readyCallback = function() {
					n.DOMReady = !0
				}
			}).then(function() {
				return e
			}).then(function() {
				var e;
				n.supports.everything || (n.readyCallback(), (e = n.source || {}).concatemoji ? t(e.concatemoji) : e.wpemoji && e.twemoji && (t(e.twemoji), t(e.wpemoji)))
			}))
		}((window, document), window._wpemojiSettings);
		/* ]]> */
	</script>
	<style id='wp-emoji-styles-inline-css' type='text/css'>
		img.wp-smiley,
		img.emoji {
			display: inline !important;
			border: none !important;
			box-shadow: none !important;
			height: 1em !important;
			width: 1em !important;
			margin: 0 0.07em !important;
			vertical-align: -0.1em !important;
			background: none !important;
			padding: 0 !important;
		}
	</style>
	<link rel='stylesheet' id='wp-block-library-css' href='../wp-includes/css/dist/block-library/style.mind70e.css?ver=6.7.5' type='text/css' media='all' />
	<style id='classic-theme-styles-inline-css' type='text/css'>
		/*! This file is auto-generated */
		.wp-block-button__link {
			color: #fff;
			background-color: #32373c;
			border-radius: 9999px;
			box-shadow: none;
			text-decoration: none;
			padding: calc(.667em + 2px) calc(1.333em + 2px);
			font-size: 1.125em
		}

		.wp-block-file__button {
			background: #32373c;
			color: #fff;
			text-decoration: none
		}
	</style>
	<style id='global-styles-inline-css' type='text/css'>
		:root {
			--wp--preset--aspect-ratio--square: 1;
			--wp--preset--aspect-ratio--4-3: 4/3;
			--wp--preset--aspect-ratio--3-4: 3/4;
			--wp--preset--aspect-ratio--3-2: 3/2;
			--wp--preset--aspect-ratio--2-3: 2/3;
			--wp--preset--aspect-ratio--16-9: 16/9;
			--wp--preset--aspect-ratio--9-16: 9/16;
			--wp--preset--color--black: #000000;
			--wp--preset--color--cyan-bluish-gray: #abb8c3;
			--wp--preset--color--white: #ffffff;
			--wp--preset--color--pale-pink: #f78da7;
			--wp--preset--color--vivid-red: #cf2e2e;
			--wp--preset--color--luminous-vivid-orange: #ff6900;
			--wp--preset--color--luminous-vivid-amber: #fcb900;
			--wp--preset--color--light-green-cyan: #7bdcb5;
			--wp--preset--color--vivid-green-cyan: #00d084;
			--wp--preset--color--pale-cyan-blue: #8ed1fc;
			--wp--preset--color--vivid-cyan-blue: #0693e3;
			--wp--preset--color--vivid-purple: #9b51e0;
			--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple: linear-gradient(135deg, rgba(6, 147, 227, 1) 0%, rgb(155, 81, 224) 100%);
			--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan: linear-gradient(135deg, rgb(122, 220, 180) 0%, rgb(0, 208, 130) 100%);
			--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange: linear-gradient(135deg, rgba(252, 185, 0, 1) 0%, rgba(255, 105, 0, 1) 100%);
			--wp--preset--gradient--luminous-vivid-orange-to-vivid-red: linear-gradient(135deg, rgba(255, 105, 0, 1) 0%, rgb(207, 46, 46) 100%);
			--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray: linear-gradient(135deg, rgb(238, 238, 238) 0%, rgb(169, 184, 195) 100%);
			--wp--preset--gradient--cool-to-warm-spectrum: linear-gradient(135deg, rgb(74, 234, 220) 0%, rgb(151, 120, 209) 20%, rgb(207, 42, 186) 40%, rgb(238, 44, 130) 60%, rgb(251, 105, 98) 80%, rgb(254, 248, 76) 100%);
			--wp--preset--gradient--blush-light-purple: linear-gradient(135deg, rgb(255, 206, 236) 0%, rgb(152, 150, 240) 100%);
			--wp--preset--gradient--blush-bordeaux: linear-gradient(135deg, rgb(254, 205, 165) 0%, rgb(254, 45, 45) 50%, rgb(107, 0, 62) 100%);
			--wp--preset--gradient--luminous-dusk: linear-gradient(135deg, rgb(255, 203, 112) 0%, rgb(199, 81, 192) 50%, rgb(65, 88, 208) 100%);
			--wp--preset--gradient--pale-ocean: linear-gradient(135deg, rgb(255, 245, 203) 0%, rgb(182, 227, 212) 50%, rgb(51, 167, 181) 100%);
			--wp--preset--gradient--electric-grass: linear-gradient(135deg, rgb(202, 248, 128) 0%, rgb(113, 206, 126) 100%);
			--wp--preset--gradient--midnight: linear-gradient(135deg, rgb(2, 3, 129) 0%, rgb(40, 116, 252) 100%);
			--wp--preset--font-size--small: 13px;
			--wp--preset--font-size--medium: 20px;
			--wp--preset--font-size--large: 36px;
			--wp--preset--font-size--x-large: 42px;
			--wp--preset--spacing--20: 0.44rem;
			--wp--preset--spacing--30: 0.67rem;
			--wp--preset--spacing--40: 1rem;
			--wp--preset--spacing--50: 1.5rem;
			--wp--preset--spacing--60: 2.25rem;
			--wp--preset--spacing--70: 3.38rem;
			--wp--preset--spacing--80: 5.06rem;
			--wp--preset--shadow--natural: 6px 6px 9px rgba(0, 0, 0, 0.2);
			--wp--preset--shadow--deep: 12px 12px 50px rgba(0, 0, 0, 0.4);
			--wp--preset--shadow--sharp: 6px 6px 0px rgba(0, 0, 0, 0.2);
			--wp--preset--shadow--outlined: 6px 6px 0px -3px rgba(255, 255, 255, 1), 6px 6px rgba(0, 0, 0, 1);
			--wp--preset--shadow--crisp: 6px 6px 0px rgba(0, 0, 0, 1);
		}

		:where(.is-layout-flex) {
			gap: 0.5em;
		}

		:where(.is-layout-grid) {
			gap: 0.5em;
		}

		body .is-layout-flex {
			display: flex;
		}

		.is-layout-flex {
			flex-wrap: wrap;
			align-items: center;
		}

		.is-layout-flex> :is(*, div) {
			margin: 0;
		}

		body .is-layout-grid {
			display: grid;
		}

		.is-layout-grid> :is(*, div) {
			margin: 0;
		}

		:where(.wp-block-columns.is-layout-flex) {
			gap: 2em;
		}

		:where(.wp-block-columns.is-layout-grid) {
			gap: 2em;
		}

		:where(.wp-block-post-template.is-layout-flex) {
			gap: 1.25em;
		}

		:where(.wp-block-post-template.is-layout-grid) {
			gap: 1.25em;
		}

		.has-black-color {
			color: var(--wp--preset--color--black) !important;
		}

		.has-cyan-bluish-gray-color {
			color: var(--wp--preset--color--cyan-bluish-gray) !important;
		}

		.has-white-color {
			color: var(--wp--preset--color--white) !important;
		}

		.has-pale-pink-color {
			color: var(--wp--preset--color--pale-pink) !important;
		}

		.has-vivid-red-color {
			color: var(--wp--preset--color--vivid-red) !important;
		}

		.has-luminous-vivid-orange-color {
			color: var(--wp--preset--color--luminous-vivid-orange) !important;
		}

		.has-luminous-vivid-amber-color {
			color: var(--wp--preset--color--luminous-vivid-amber) !important;
		}

		.has-light-green-cyan-color {
			color: var(--wp--preset--color--light-green-cyan) !important;
		}

		.has-vivid-green-cyan-color {
			color: var(--wp--preset--color--vivid-green-cyan) !important;
		}

		.has-pale-cyan-blue-color {
			color: var(--wp--preset--color--pale-cyan-blue) !important;
		}

		.has-vivid-cyan-blue-color {
			color: var(--wp--preset--color--vivid-cyan-blue) !important;
		}

		.has-vivid-purple-color {
			color: var(--wp--preset--color--vivid-purple) !important;
		}

		.has-black-background-color {
			background-color: var(--wp--preset--color--black) !important;
		}

		.has-cyan-bluish-gray-background-color {
			background-color: var(--wp--preset--color--cyan-bluish-gray) !important;
		}

		.has-white-background-color {
			background-color: var(--wp--preset--color--white) !important;
		}

		.has-pale-pink-background-color {
			background-color: var(--wp--preset--color--pale-pink) !important;
		}

		.has-vivid-red-background-color {
			background-color: var(--wp--preset--color--vivid-red) !important;
		}

		.has-luminous-vivid-orange-background-color {
			background-color: var(--wp--preset--color--luminous-vivid-orange) !important;
		}

		.has-luminous-vivid-amber-background-color {
			background-color: var(--wp--preset--color--luminous-vivid-amber) !important;
		}

		.has-light-green-cyan-background-color {
			background-color: var(--wp--preset--color--light-green-cyan) !important;
		}

		.has-vivid-green-cyan-background-color {
			background-color: var(--wp--preset--color--vivid-green-cyan) !important;
		}

		.has-pale-cyan-blue-background-color {
			background-color: var(--wp--preset--color--pale-cyan-blue) !important;
		}

		.has-vivid-cyan-blue-background-color {
			background-color: var(--wp--preset--color--vivid-cyan-blue) !important;
		}

		.has-vivid-purple-background-color {
			background-color: var(--wp--preset--color--vivid-purple) !important;
		}

		.has-black-border-color {
			border-color: var(--wp--preset--color--black) !important;
		}

		.has-cyan-bluish-gray-border-color {
			border-color: var(--wp--preset--color--cyan-bluish-gray) !important;
		}

		.has-white-border-color {
			border-color: var(--wp--preset--color--white) !important;
		}

		.has-pale-pink-border-color {
			border-color: var(--wp--preset--color--pale-pink) !important;
		}

		.has-vivid-red-border-color {
			border-color: var(--wp--preset--color--vivid-red) !important;
		}

		.has-luminous-vivid-orange-border-color {
			border-color: var(--wp--preset--color--luminous-vivid-orange) !important;
		}

		.has-luminous-vivid-amber-border-color {
			border-color: var(--wp--preset--color--luminous-vivid-amber) !important;
		}

		.has-light-green-cyan-border-color {
			border-color: var(--wp--preset--color--light-green-cyan) !important;
		}

		.has-vivid-green-cyan-border-color {
			border-color: var(--wp--preset--color--vivid-green-cyan) !important;
		}

		.has-pale-cyan-blue-border-color {
			border-color: var(--wp--preset--color--pale-cyan-blue) !important;
		}

		.has-vivid-cyan-blue-border-color {
			border-color: var(--wp--preset--color--vivid-cyan-blue) !important;
		}

		.has-vivid-purple-border-color {
			border-color: var(--wp--preset--color--vivid-purple) !important;
		}

		.has-vivid-cyan-blue-to-vivid-purple-gradient-background {
			background: var(--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple) !important;
		}

		.has-light-green-cyan-to-vivid-green-cyan-gradient-background {
			background: var(--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan) !important;
		}

		.has-luminous-vivid-amber-to-luminous-vivid-orange-gradient-background {
			background: var(--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange) !important;
		}

		.has-luminous-vivid-orange-to-vivid-red-gradient-background {
			background: var(--wp--preset--gradient--luminous-vivid-orange-to-vivid-red) !important;
		}

		.has-very-light-gray-to-cyan-bluish-gray-gradient-background {
			background: var(--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray) !important;
		}

		.has-cool-to-warm-spectrum-gradient-background {
			background: var(--wp--preset--gradient--cool-to-warm-spectrum) !important;
		}

		.has-blush-light-purple-gradient-background {
			background: var(--wp--preset--gradient--blush-light-purple) !important;
		}

		.has-blush-bordeaux-gradient-background {
			background: var(--wp--preset--gradient--blush-bordeaux) !important;
		}

		.has-luminous-dusk-gradient-background {
			background: var(--wp--preset--gradient--luminous-dusk) !important;
		}

		.has-pale-ocean-gradient-background {
			background: var(--wp--preset--gradient--pale-ocean) !important;
		}

		.has-electric-grass-gradient-background {
			background: var(--wp--preset--gradient--electric-grass) !important;
		}

		.has-midnight-gradient-background {
			background: var(--wp--preset--gradient--midnight) !important;
		}

		.has-small-font-size {
			font-size: var(--wp--preset--font-size--small) !important;
		}

		.has-medium-font-size {
			font-size: var(--wp--preset--font-size--medium) !important;
		}

		.has-large-font-size {
			font-size: var(--wp--preset--font-size--large) !important;
		}

		.has-x-large-font-size {
			font-size: var(--wp--preset--font-size--x-large) !important;
		}

		:where(.wp-block-post-template.is-layout-flex) {
			gap: 1.25em;
		}

		:where(.wp-block-post-template.is-layout-grid) {
			gap: 1.25em;
		}

		:where(.wp-block-columns.is-layout-flex) {
			gap: 2em;
		}

		:where(.wp-block-columns.is-layout-grid) {
			gap: 2em;
		}

		:root :where(.wp-block-pullquote) {
			font-size: 1.5em;
			line-height: 1.6;
		}
	</style>
	<link rel='stylesheet' id='contact-form-7-css' href='../wp-content/plugins/contact-form-7/includes/css/styles5697.css?ver=5.5.3' type='text/css' media='all' />
	<link rel='stylesheet' id='newsletter-css' href='../wp-content/plugins/newsletter/styleba31.css?ver=7.3.3' type='text/css' media='all' />
	<link rel='stylesheet' id='js_composer_front-css' href='../wp-content/plugins/composer/assets/css/js_composer.min7263.css?ver=5.4.4' type='text/css' media='all' />
	<script type="text/javascript" src="../wp-includes/js/jquery/jquery.minf43b.js?ver=3.7.1" id="jquery-core-js"></script>
	<script type="text/javascript" src="../wp-includes/js/jquery/jquery-migrate.min5589.js?ver=3.4.1" id="jquery-migrate-js"></script>
	<link rel="https://api.w.org/" href="../wp-json/index.php" />
	<link rel="alternate" title="JSON" type="application/json" href="../wp-json/wp/v2/pages/514.json" />
	<link rel="EditURI" type="application/rsd+xml" title="RSD" href="../xmlrpc0db0.php?rsd" />
	<meta name="generator" content="WordPress 6.7.5" />
	<link rel='shortlink' href='../indexcaae.php?p=514' />
	<link rel="alternate" title="oEmbed (JSON)" type="application/json+oembed" href="../wp-json/oembed/1.0/embed6fc1.json?url=https%3A%2F%2Fgoringmedicalprojects.com%2Fprivacy-policy%2F" />
	<link rel="alternate" title="oEmbed (XML)" type="text/xml+oembed" href="../wp-json/oembed/1.0/embedcdac?url=https%3A%2F%2Fgoringmedicalprojects.com%2Fprivacy-policy%2F&amp;format=xml" />
	<meta name="generator" content="Powered by WPBakery Page Builder - drag and drop page builder for WordPress." />
	<!--[if lte IE 9]><link rel="stylesheet" type="text/css" href="https://goringmedicalprojects.com/wp-content/plugins/composer/assets/css/vc_lte_ie9.min.css" media="screen"><![endif]-->
	<style type="text/css" data-type="vc_shortcodes-custom-css">
		.vc_custom_1603743541820 {
			margin-top: 0px !important;
			margin-bottom: 0px !important;
			border-top-width: 0px !important;
			border-bottom-width: 0px !important;
			padding-top: 0px !important;
			padding-bottom: 0px !important;
		}

		.vc_custom_1603742557135 {
			margin-top: 0px !important;
			margin-bottom: 0px !important;
			border-top-width: 0px !important;
			border-bottom-width: 0px !important;
			padding-top: 0px !important;
			padding-bottom: 0px !important;
		}

		.vc_custom_1603743246360 {
			margin-top: 0px !important;
			margin-bottom: 0px !important;
			border-top-width: 0px !important;
			border-bottom-width: 0px !important;
			padding-top: 0px !important;
			padding-bottom: 0px !important;
		}
	</style><noscript>
		<style type="text/css">
			.wpb_animate_when_almost_visible {
				opacity: 1;
			}
		</style>
	</noscript>
	<meta name="google-site-verification" content="cbsGP5B99j-IeRU774Y_uddg6K0KKY8piB4HFsX93eg" />
</head>

<body class="page-template-default page page-id-514 wpb-js-composer js-comp-ver-5.4.4 vc_responsive">
	<!-- Wrapper Start -->
	<div id="preloader" class="load">
		<video autoplay="autoplay" muted="" playsinline="">
			<source src="../wp-content/uploads/2020/11/Wayne_Goring_Logo_Opt2_Final_Cut.mp4" type="video/mp4">Your browser does not support the video tag.
		</video>
	</div>
	<div id="wrapper">

		<div class="top-sec">
			<div class="container">
				<div class="row LC-row">

					<div class="col-md-3 logo-area">
						<div class="logo_sec">
							<a href="../index.php" title="GORING MEDICAL" rel="home">
								<img class="img-responsive" src="../wp-content/themes/goring/images/logo.png" alt="GORING MEDICAL" />
							</a>
						</div>
						<div class="resnav-toggle">
							<button type="button" data-id="#resnav" data-action="nav-toggle"><i class="fa fa-bars"></i></button>
						</div>
					</div>

					<div class="col-md-9 col-md-offset-0 TA-area">
						<div class="top-address">
							<div class="address-box">
								<span>Email Address</span>
								<a href="mailto:info@goringmedicalprojects.com">info@goringmedicalprojects.com</a>
							</div>
							<div class="address-box">
								<span>Phone Number</span>
								<a href="tel:+44 (0) 7584 598 649">+44 (0) 7584 598 649</a>
							</div>
							<div class="address-box">
								<span>Office Address</span>
								<p>Goring Medical Projects Ltd, 1 Trinity Farm Cottages, Barnby Moor, Retford, Notts, UK, DN22 8QW</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<header class="header-sec">
			<div class="container">
				<div class="row Menu-Search-row">
					<div class="col-md-8 Menu-col">
						<div class="navbar navbar-inverse navbar-static-top" role="navigation">
							<div class="navbar-header">
								<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
									<span class="sr-only">Toggle navigation</span>
									<span class="icon-bar"></span>
									<span class="icon-bar"></span>
									<span class="icon-bar"></span>
								</button>
							</div>
							<div class="navbar-collapse collapse">
								<div class="menu-main-menu-container">
									<ul id="menu-main-menu" class="nav navbar-nav">
										<li id="menu-item-23" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-home menu-item-23"><a title="Home" href="../index.php">Home</a></li>
										<li id="menu-item-22" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-22"><a title="About Us" href="../about-us/index.php">About Us</a></li>
										<li id="menu-item-21" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-21"><a title="Services" href="../services/index.php">Services</a></li>
										<li id="menu-item-20" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-20"><a title="Testimonials" href="../testimonials/index.php">Testimonials</a></li>
										<li id="menu-item-275" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-275"><a title="Project Gallery" href="../my-projects/index.php">Project Gallery</a></li>
										<li id="menu-item-19" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-19"><a title="Case Studies" href="../case-studies/index.php">Case Studies</a></li>
										<li id="menu-item-602" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-602"><a title="Blog" href="../blog/index.php">Blog</a></li>
										<li id="menu-item-18" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-18"><a title="Contact Us" href="../contact-us/index.php">Contact Us</a></li>
									</ul>
								</div>
							</div>
						</div>
					</div>

					<div class="col-md-3 col-md-offset-1 Search-col">
						<div class="search-box">
							<div class="widget">
								<form role="search" method="get" id="searchform" class="searchform" action="https://goringmedicalprojects.com/">
									<div>
										<label class="screen-reader-text" for="s">Search for:</label>
										<input type="text" value="" name="s" id="s" />
										<input type="submit" id="searchsubmit" value="Search" />
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</header>
		<div class="resnav-collapse" id="resnav">
			<button type="button" data-id="#resnav" data-action="nav-close" class="resnav-close"><i class="fa fa-times"></i></button>
			<div class="resnav-area">
				<div class="resnav-search-box">
					<div class="widget">
						<form role="search" method="get" id="searchform" class="searchform" action="https://goringmedicalprojects.com/">
							<div>
								<label class="screen-reader-text" for="s">Search for:</label>
								<input type="text" value="" name="s" id="s" />
								<input type="submit" id="searchsubmit" value="Search" />
							</div>
						</form>
					</div>
				</div>
				<div class="resnav-nav-menu-box">
					<ul id="menu-main-menu-1" class="list-unstyled mb-0">
						<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-home menu-item-23"><a title="Home" href="../index.php">Home</a></li>
						<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-22"><a title="About Us" href="../about-us/index.php">About Us</a></li>
						<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-21"><a title="Services" href="../services/index.php">Services</a></li>
						<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-20"><a title="Testimonials" href="../testimonials/index.php">Testimonials</a></li>
						<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-275"><a title="Project Gallery" href="../my-projects/index.php">Project Gallery</a></li>
						<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-19"><a title="Case Studies" href="../case-studies/index.php">Case Studies</a></li>
						<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-602"><a title="Blog" href="../blog/index.php">Blog</a></li>
						<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-18"><a title="Contact Us" href="../contact-us/index.php">Contact Us</a></li>
					</ul>
				</div>
				<div class="resnav-contact-details">
					<ul class="list-unstyled mb-0">
						<li><strong>Phone:</strong>
							<span><a href="tel:+44 (0) 7584 598 649">+44 (0) 7584 598 649</a></span>
						</li>
						<li><strong>Email:</strong>
							<span><a href="mailto:info@goringmedicalprojects.com">info@goringmedicalprojects.com</a></span>
						</li>
						<li><strong>Address:</strong>
							<span>
								<p>Goring Medical Projects Ltd, 1 Trinity Farm Cottages, Barnby Moor, Retford, Notts, UK, DN22 8QW</p>
							</span>
						</li>
					</ul>
				</div>
				<div class="resnav-social-links">
					<ul class="list-unstyled mb-0">
						<li><a href="https://www.facebook.com/Goring-Medical-Projects-100270068728705/" target="_blank"><i class="fa fa-facebook-f"></i></a></li>
						<!-- 							<li><a href="javascript:;"><i class="fab fa-twitter"></i></a></li>
<li><a href="javascript:;"><i class="fab fa-instagram"></i></a></li> -->
						<li><a href="https://www.linkedin.com/company/goring-medical-projects-ltd" target="_blank"><i class="fa fa-linkedin"></i></a></li>
					</ul>
				</div>
			</div>
		</div>



		<section style="background-image: url('../wp-content/uploads/2019/02/Banner_Image.png');" class="page-banner">

			<!-- <img class="img-responsive" width="100%" src="" alt=""> -->

			<div class="text-box banner-content">

				<h1 class="banner-title page-title">Privacy Policy</h1>

			</div>

		</section>

		<section class="master_page">

			<div class="container">

				<div class="vc_row wpb_row vc_row-fluid static-sec sec-static vc_custom_1603743541820 vc_row-has-fill">
					<div class="wpb_column vc_column_container vc_col-sm-12 vc_col-has-fill">
						<div class="vc_column-inner vc_custom_1603742557135">
							<div class="wpb_wrapper">
								<div class="wpb_text_column wpb_content_element  vc_custom_1603743246360">
									<div class="wpb_wrapper">
										<h2>About your Privacy</h2>
										<p>Your privacy is extremely important to us. It is Goring Medical Ltd’s policy to respect your privacy regarding any information we may collect from you across our website, <a href="../index.php">http://www.goringmedicalprojects.com</a>, and other sites we own and operate.</p>
										<p>We rarely ask for personal information and only when we require it to provide a service to you. We collect it by fair and lawful means, with your knowledge and consent. We also let you know why we are collecting it and how it will be used. We are transparent and conduct appropriate measures to ensure that your privacy is maintained.</p>
										<p>We only retain collected information for as long as necessary to provide you with your requested service. What data we store, we will protect within commercially acceptable means to prevent loss and theft, as well as unauthorised access, disclosure, copying, use or modification.</p>
										<p>We don’t share any personally identifying information publicly or with third parties, except when required to by law.</p>
										<p>Our website may link to external sites that are not operated by us. Please be aware that we have no control over the content and practices of these sites and cannot accept responsibility or liability for their respective privacy policies.</p>
										<p>You are free to refuse our request for your personal information, with the understanding that we may be unable to provide you with some of your desired services.</p>
										<p>Your continued use of our website will be regarded as acceptance of our practices around privacy and personal information. If you have any questions about how we handle user data and personal information, feel free to contact us.</p>
										<h3>OUR DATA PROTECTION PRINCIPLES</h3>
										<p>We will make sure that we comply with Data protection law which says that the information we hold about you must be:</p>
										<ul>
											<li>Used lawfully, fairly and in a transparent way</li>
											<li>Collected only for valid purposes that we have clearly explained to you and not used in any way that is compatible with those purposes</li>
											<li>Relevant to the purposes we have told you about and limited only to those purposes</li>
											<li>Accurate and kept up to date</li>
											<li>Kept only as long as necessary for the purposes we have told you about</li>
											<li>Kept securely</li>
										</ul>
										<h3>OUR LEGAL BASIS FOR PROCESSING PERSONAL DATA</h3>
										<p>Goring Medical Projects Ltd relies on “legitimate interests” as a basis for processing personal information.</p>
										<p>We process data on business professionals to support sales and marketing and ongoing support of our business and its functions.  The individual benefits from improved targeting and relevancy of our products and services.  The individual may be contacted based on our understanding of their role within a company, with information we have identified as relevant to that role.  We ensure that all marketing communications provide a mechanism for the individual to unsubscribe from all communications.  We also provide a preference centre for individuals to manage the types of email content they would like to receive, and we have set up a separate data controller email address for people who wish to be removed from our database entirely.</p>
										<h3>INFORMATION COLLECTION AND USE</h3>
										<p>While using our website, we may ask you to provide us with certain personally identifiable information that can be used to contact or identify you.  Personal identifiable information may include, but is not limited to your name, email address and postcode.</p>
										<h3>LOG DATA</h3>
										<p>Like many site operators, we collect information that your browser sends whenever you visit our Site.  This log data may include information such as your computer’s Internet Protocol (IP) address, browser type, browser version, the pages of our site that you visit, the time and date of your visit, the time spent on those pages and other statistics.  This information is used by Goring Medical Projects Ltd for the operation of the service, to maintain the quality of the service, and to provide general statistics regarding the use of the Goring Medical Projects Ltd website.</p>
										<p>In addition, we may use third party services such as Google Analytics that collect, monitor and analyse this data.</p>
										<h3>USE OF YOUR PERSONAL INFORMATION</h3>
										<p>Goring Medical Projects Ltd collects and uses your personal information to operate the Goring Medical Projects Ltd site and deliver our services.  We may use your personal information to inform you of products or services available from Goring Medical Projects Ltd and its affiliates.</p>
										<p>Goring Medical Projects Ltd does not sell, rent or lease its customer lists to third parties.  Goring Medical Projects Ltd may, from time to time, contact you on behalf of external business partners about a particular offering that may be of interest to you.  In those cases, your unique personally identifiable information (e-mail, name, address, telephone number) is not transferred to the third party.</p>
										<h3>COOKIES</h3>
										<p>Cookies are files with small amounts of data, which may include an anonymous unique identifier.  Cookies are sent to your browser from a website and stored on your computer’s hard drive.</p>
										<p>Like many sites, we use “cookies” to collect information.  You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.  However, if you do not accept cookies, you may not be able to use some of our Site.</p>
										<h3>SECURITY OF YOUR PERSONAL INFORMATION</h3>
										<p>Goring Medical Projects Ltd secures your personal information from unauthorised access, use or disclosure.  Goring Medical Projects Ltd secures the personally identifiable information you provide on computer servers in a controlled, secure environment, protected from unauthorised access, use or disclosure.</p>
										<h3>CHANGES TO THIS STATEMENT</h3>
										<p>This privacy policy will remain in effect except with respect to any changes in its provisions in the future, which will be in effect immediately after being posted on this page.</p>
										<p>We reserve the right to update or change our Privacy Policy at any time and you should check this Privacy Policy periodically.  Your continued use of the Service after we post any modifications to the Privacy Policy on this page will constitute your acknowledgement of the modifications and your consent to abide and be bound by the modified Privacy Policy.</p>
										<p>If we make any material changes to this Privacy Policy, we will notify you either through the email address you have provided us, or by placing a prominent notice on our website.</p>
										<h3>CONTACT US</h3>
										<p>If you have any questions about this Privacy Policy, please email info@goringmedicalprojects.com</p>
										<p>This policy is effective as of 13th September 2020.</p>

									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>

		</section>






		<footer class="footer-sec">
			<div class="container">
				<div class="row">
					<div class="col-md-3 footContent">
						<div class="footer-bio">
							<h4>ABOUT US</h4>
							<div class="footer-social">
								<a href="https://www.linkedin.com/company/goring-medical-projects-ltd" target="_blank"><span><i class="fa fa-linkedin"></i></span>Follow us on Linkedin</a>
							</div>
							<div class="footer-social">
								<a href="https://www.facebook.com/Goring-Medical-Projects-100270068728705/" target="_blank"><span><i class="fa fa-facebook"></i></span>Follow us on FaceBook</a>
							</div>

						</div>
					</div>
					<div class="col-md-6 footContent">
						<div class="row footer-bio footer-nav">
							<div class="col-md-4 col-lg-4 col-sm-4 footLinks">
								<h4>QUICK LINK</h4>
								<div class="menu-main-menu-container">
									<ul id="menu-main-menu-2" class="">
										<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-home menu-item-23"><a href="../index.php">Home</a></li>
										<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-22"><a href="../about-us/index.php">About Us</a></li>
										<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-21"><a href="../services/index.php">Services</a></li>
										<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-20"><a href="../testimonials/index.php">Testimonials</a></li>
										<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-275"><a href="../my-projects/index.php">Project Gallery</a></li>
										<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-19"><a href="../case-studies/index.php">Case Studies</a></li>
										<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-602"><a href="../blog/index.php">Blog</a></li>
										<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-18"><a href="../contact-us/index.php">Contact Us</a></li>
									</ul>
								</div>
							</div>
							<div class="col-md-5 col-lg-5 col-sm-5 footLinks">
								<h4>Services Links</h4>
								<div class="menu-services-menu-container">
									<ul id="menu-services-menu" class="">
										<li id="menu-item-127" class="menu-item menu-item-type-post_type menu-item-object-service menu-item-127"><a href="../service/project-management/index.php">Project management</a></li>
										<li id="menu-item-128" class="menu-item menu-item-type-post_type menu-item-object-service menu-item-128"><a href="../service/system-inspection-and-audit/index.php">QA, system inspection, <br> evaluation &#038; reporting <br> (MRI PDF &#038; CT PDF)</a></li>
										<li id="menu-item-129" class="menu-item menu-item-type-post_type menu-item-object-service menu-item-129"><a href="../service/system-installation/index.php">Medical equipment deinstallation, installation &#038; relocation</a></li>
										<li id="menu-item-130" class="menu-item menu-item-type-post_type menu-item-object-service menu-item-130"><a href="../service/system-ramp-down/index.php">Ramp down/shimming</a></li>
										<li id="menu-item-131" class="menu-item menu-item-type-post_type menu-item-object-service menu-item-131"><a href="../service/mobile-relocatable-design/index.php">Mobile, modular and relocatable design</a></li>
										<li id="menu-item-132" class="menu-item menu-item-type-post_type menu-item-object-service menu-item-132"><a href="../service/patient-data-deletion/index.php">Blancco certified data deletion</a></li>
										<li id="menu-item-568" class="menu-item menu-item-type-post_type menu-item-object-service menu-item-568"><a href="../service/helium-leak-detector-and-cold-head-exchange/index.php">Helium leak detection, coldhead exchange, repairs and rigging tool rental</a></li>
									</ul>
								</div>
							</div>
							<div class="col-md-3 col-lg-3 col-sm-3 footLinks">
								<h4>Legals</h4>
								<div class="menu-legal-menu-container">
									<ul id="menu-legal-menu" class="">
										<li id="menu-item-519" class="menu-item menu-item-type-post_type menu-item-object-page current-menu-item page_item page-item-514 current_page_item menu-item-519"><a href="index.php" aria-current="page">Privacy Policy</a></li>
										<li id="menu-item-518" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-518"><a href="../terms-conditions/index.php">Terms &#038; Conditions</a></li>
									</ul>
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-3 footContent">
						<div class="footer-bio">
							<h4>SIGN UP FOR NEWSLETTER</h4>
							<div class="newsletter">
								<div class="widget">
									<div class="tnp tnp-widget">
										<form method="post" action="https://goringmedicalprojects.com/?na=s">

											<input type="hidden" name="nr" value="widget"><input type="hidden" name="nlang" value="">
											<div class="tnp-field tnp-field-email"><label for="tnp-1">Email</label>
												<input class="tnp-email" type="email" name="ne" id="tnp-1" value="" required>
											</div>
											<div class="tnp-field tnp-field-button"><input class="tnp-submit" type="submit" value="Subscribe">
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="copyright">
				<p>© Copyright, Goring Medical Projects Ltd, All Rights Reserved.</p>
			</div>
		</footer>
	</div> <!--Wrapper End-->


	<!-- Bootstrap core JavaScript
================================================== -->
	<!-- Placed at the end of the document so the pages load faster -->
	<script type="text/javascript" src="../wp-includes/js/dist/vendor/wp-polyfill.min2c7c.js?ver=3.15.0" id="wp-polyfill-js"></script>
	<script type="text/javascript" id="contact-form-7-js-extra">
		/* <![CDATA[ */
		var wpcf7 = {
			"api": {
				"root": "https:\/\/goringmedicalprojects.com\/wp-json\/",
				"namespace": "contact-form-7\/v1"
			}
		};
		/* ]]> */
	</script>
	<script type="text/javascript" src="../wp-content/plugins/contact-form-7/includes/js/index5697.js?ver=5.5.3" id="contact-form-7-js"></script>
	<script type="text/javascript" src="../wp-content/plugins/composer/assets/js/dist/js_composer_front.min7263.js?ver=5.4.4" id="wpb_composer_front_js-js"></script>
	<script src="../wp-content/themes/goring/js/bootstrap.min.js"></script>
	<script src="../wp-content/themes/goring/slick/slick.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
	<script src="../wp-content/themes/goring/js/custom.js"></script>
	<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>


</body>

<!-- Mirrored from goringmedicalprojects.com/privacy-policy/ by HTTrack Website Copier/3.x [XR&CO'2014], Thu, 06 Aug 2026 14:39:43 GMT -->

</html>

<?php ob_end_flush(); ?>