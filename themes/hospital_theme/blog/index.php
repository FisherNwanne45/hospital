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
	
<!-- Mirrored from goringmedicalprojects.com/blog/ by HTTrack Website Copier/3.x [XR&CO'2014], Thu, 06 Aug 2026 14:38:07 GMT -->
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=UTF-8" /><!-- /Added by HTTrack -->
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
			Blog - GORING MEDICAL - GORING MEDICAL		</title>
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
			<style>img:is([sizes="auto" i], [sizes^="auto," i]) { contain-intrinsic-size: 3000px 1500px }</style>
	
<!-- Search Engine Optimization by Rank Math - https://s.rankmath.com/home -->
<meta name="robots" content="index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large"/>
<link rel="canonical" href="index.php" />
<meta property="og:locale" content="en_US" />
<meta property="og:type" content="article" />
<meta property="og:title" content="Blog - GORING MEDICAL" />
<meta property="og:url" content="index.php" />
<meta property="og:site_name" content="GORING MEDICAL" />
<meta property="og:updated_time" content="2021-02-09T11:33:57+00:00" />
<meta property="og:image" content="../wp-content/uploads/2019/02/Banner_Image.png" />
<meta property="og:image:secure_url" content="../wp-content/uploads/2019/02/Banner_Image.png" />
<meta property="og:image:width" content="1600" />
<meta property="og:image:height" content="850" />
<meta property="og:image:alt" content="Blog" />
<meta property="og:image:type" content="image/png" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="Blog - GORING MEDICAL" />
<meta name="twitter:image" content="../wp-content/uploads/2019/02/Banner_Image.png" />
<!-- /Rank Math WordPress SEO plugin -->

<script type="text/javascript">
/* <![CDATA[ */
window._wpemojiSettings = {"baseUrl":"https:\/\/s.w.org\/images\/core\/emoji\/15.0.3\/72x72\/","ext":".png","svgUrl":"https:\/\/s.w.org\/images\/core\/emoji\/15.0.3\/svg\/","svgExt":".svg","source":{"concatemoji":"https:\/\/goringmedicalprojects.com\/wp-includes\/js\/wp-emoji-release.min.js?ver=6.7.5"}};
/*! This file is auto-generated */
!function(i,n){var o,s,e;function c(e){try{var t={supportTests:e,timestamp:(new Date).valueOf()};sessionStorage.setItem(o,JSON.stringify(t))}catch(e){}}function p(e,t,n){e.clearRect(0,0,e.canvas.width,e.canvas.height),e.fillText(t,0,0);var t=new Uint32Array(e.getImageData(0,0,e.canvas.width,e.canvas.height).data),r=(e.clearRect(0,0,e.canvas.width,e.canvas.height),e.fillText(n,0,0),new Uint32Array(e.getImageData(0,0,e.canvas.width,e.canvas.height).data));return t.every(function(e,t){return e===r[t]})}function u(e,t,n){switch(t){case"flag":return n(e,"\ud83c\udff3\ufe0f\u200d\u26a7\ufe0f","\ud83c\udff3\ufe0f\u200b\u26a7\ufe0f")?!1:!n(e,"\ud83c\uddfa\ud83c\uddf3","\ud83c\uddfa\u200b\ud83c\uddf3")&&!n(e,"\ud83c\udff4\udb40\udc67\udb40\udc62\udb40\udc65\udb40\udc6e\udb40\udc67\udb40\udc7f","\ud83c\udff4\u200b\udb40\udc67\u200b\udb40\udc62\u200b\udb40\udc65\u200b\udb40\udc6e\u200b\udb40\udc67\u200b\udb40\udc7f");case"emoji":return!n(e,"\ud83d\udc26\u200d\u2b1b","\ud83d\udc26\u200b\u2b1b")}return!1}function f(e,t,n){var r="undefined"!=typeof WorkerGlobalScope&&self instanceof WorkerGlobalScope?new OffscreenCanvas(300,150):i.createElement("canvas"),a=r.getContext("2d",{willReadFrequently:!0}),o=(a.textBaseline="top",a.font="600 32px Arial",{});return e.forEach(function(e){o[e]=t(a,e,n)}),o}function t(e){var t=i.createElement("script");t.src=e,t.defer=!0,i.head.appendChild(t)}"undefined"!=typeof Promise&&(o="wpEmojiSettingsSupports",s=["flag","emoji"],n.supports={everything:!0,everythingExceptFlag:!0},e=new Promise(function(e){i.addEventListener("DOMContentLoaded",e,{once:!0})}),new Promise(function(t){var n=function(){try{var e=JSON.parse(sessionStorage.getItem(o));if("object"==typeof e&&"number"==typeof e.timestamp&&(new Date).valueOf()<e.timestamp+604800&&"object"==typeof e.supportTests)return e.supportTests}catch(e){}return null}();if(!n){if("undefined"!=typeof Worker&&"undefined"!=typeof OffscreenCanvas&&"undefined"!=typeof URL&&URL.createObjectURL&&"undefined"!=typeof Blob)try{var e="postMessage("+f.toString()+"("+[JSON.stringify(s),u.toString(),p.toString()].join(",")+"));",r=new Blob([e],{type:"text/javascript"}),a=new Worker(URL.createObjectURL(r),{name:"wpTestEmojiSupports"});return void(a.onmessage=function(e){c(n=e.data),a.terminate(),t(n)})}catch(e){}c(n=f(s,u,p))}t(n)}).then(function(e){for(var t in e)n.supports[t]=e[t],n.supports.everything=n.supports.everything&&n.supports[t],"flag"!==t&&(n.supports.everythingExceptFlag=n.supports.everythingExceptFlag&&n.supports[t]);n.supports.everythingExceptFlag=n.supports.everythingExceptFlag&&!n.supports.flag,n.DOMReady=!1,n.readyCallback=function(){n.DOMReady=!0}}).then(function(){return e}).then(function(){var e;n.supports.everything||(n.readyCallback(),(e=n.source||{}).concatemoji?t(e.concatemoji):e.wpemoji&&e.twemoji&&(t(e.twemoji),t(e.wpemoji)))}))}((window,document),window._wpemojiSettings);
/* ]]> */
</script>
<style id='wp-emoji-styles-inline-css' type='text/css'>

	img.wp-smiley, img.emoji {
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
.wp-block-button__link{color:#fff;background-color:#32373c;border-radius:9999px;box-shadow:none;text-decoration:none;padding:calc(.667em + 2px) calc(1.333em + 2px);font-size:1.125em}.wp-block-file__button{background:#32373c;color:#fff;text-decoration:none}
</style>
<style id='global-styles-inline-css' type='text/css'>
:root{--wp--preset--aspect-ratio--square: 1;--wp--preset--aspect-ratio--4-3: 4/3;--wp--preset--aspect-ratio--3-4: 3/4;--wp--preset--aspect-ratio--3-2: 3/2;--wp--preset--aspect-ratio--2-3: 2/3;--wp--preset--aspect-ratio--16-9: 16/9;--wp--preset--aspect-ratio--9-16: 9/16;--wp--preset--color--black: #000000;--wp--preset--color--cyan-bluish-gray: #abb8c3;--wp--preset--color--white: #ffffff;--wp--preset--color--pale-pink: #f78da7;--wp--preset--color--vivid-red: #cf2e2e;--wp--preset--color--luminous-vivid-orange: #ff6900;--wp--preset--color--luminous-vivid-amber: #fcb900;--wp--preset--color--light-green-cyan: #7bdcb5;--wp--preset--color--vivid-green-cyan: #00d084;--wp--preset--color--pale-cyan-blue: #8ed1fc;--wp--preset--color--vivid-cyan-blue: #0693e3;--wp--preset--color--vivid-purple: #9b51e0;--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple: linear-gradient(135deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%);--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan: linear-gradient(135deg,rgb(122,220,180) 0%,rgb(0,208,130) 100%);--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange: linear-gradient(135deg,rgba(252,185,0,1) 0%,rgba(255,105,0,1) 100%);--wp--preset--gradient--luminous-vivid-orange-to-vivid-red: linear-gradient(135deg,rgba(255,105,0,1) 0%,rgb(207,46,46) 100%);--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray: linear-gradient(135deg,rgb(238,238,238) 0%,rgb(169,184,195) 100%);--wp--preset--gradient--cool-to-warm-spectrum: linear-gradient(135deg,rgb(74,234,220) 0%,rgb(151,120,209) 20%,rgb(207,42,186) 40%,rgb(238,44,130) 60%,rgb(251,105,98) 80%,rgb(254,248,76) 100%);--wp--preset--gradient--blush-light-purple: linear-gradient(135deg,rgb(255,206,236) 0%,rgb(152,150,240) 100%);--wp--preset--gradient--blush-bordeaux: linear-gradient(135deg,rgb(254,205,165) 0%,rgb(254,45,45) 50%,rgb(107,0,62) 100%);--wp--preset--gradient--luminous-dusk: linear-gradient(135deg,rgb(255,203,112) 0%,rgb(199,81,192) 50%,rgb(65,88,208) 100%);--wp--preset--gradient--pale-ocean: linear-gradient(135deg,rgb(255,245,203) 0%,rgb(182,227,212) 50%,rgb(51,167,181) 100%);--wp--preset--gradient--electric-grass: linear-gradient(135deg,rgb(202,248,128) 0%,rgb(113,206,126) 100%);--wp--preset--gradient--midnight: linear-gradient(135deg,rgb(2,3,129) 0%,rgb(40,116,252) 100%);--wp--preset--font-size--small: 13px;--wp--preset--font-size--medium: 20px;--wp--preset--font-size--large: 36px;--wp--preset--font-size--x-large: 42px;--wp--preset--spacing--20: 0.44rem;--wp--preset--spacing--30: 0.67rem;--wp--preset--spacing--40: 1rem;--wp--preset--spacing--50: 1.5rem;--wp--preset--spacing--60: 2.25rem;--wp--preset--spacing--70: 3.38rem;--wp--preset--spacing--80: 5.06rem;--wp--preset--shadow--natural: 6px 6px 9px rgba(0, 0, 0, 0.2);--wp--preset--shadow--deep: 12px 12px 50px rgba(0, 0, 0, 0.4);--wp--preset--shadow--sharp: 6px 6px 0px rgba(0, 0, 0, 0.2);--wp--preset--shadow--outlined: 6px 6px 0px -3px rgba(255, 255, 255, 1), 6px 6px rgba(0, 0, 0, 1);--wp--preset--shadow--crisp: 6px 6px 0px rgba(0, 0, 0, 1);}:where(.is-layout-flex){gap: 0.5em;}:where(.is-layout-grid){gap: 0.5em;}body .is-layout-flex{display: flex;}.is-layout-flex{flex-wrap: wrap;align-items: center;}.is-layout-flex > :is(*, div){margin: 0;}body .is-layout-grid{display: grid;}.is-layout-grid > :is(*, div){margin: 0;}:where(.wp-block-columns.is-layout-flex){gap: 2em;}:where(.wp-block-columns.is-layout-grid){gap: 2em;}:where(.wp-block-post-template.is-layout-flex){gap: 1.25em;}:where(.wp-block-post-template.is-layout-grid){gap: 1.25em;}.has-black-color{color: var(--wp--preset--color--black) !important;}.has-cyan-bluish-gray-color{color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-white-color{color: var(--wp--preset--color--white) !important;}.has-pale-pink-color{color: var(--wp--preset--color--pale-pink) !important;}.has-vivid-red-color{color: var(--wp--preset--color--vivid-red) !important;}.has-luminous-vivid-orange-color{color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-amber-color{color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-light-green-cyan-color{color: var(--wp--preset--color--light-green-cyan) !important;}.has-vivid-green-cyan-color{color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-pale-cyan-blue-color{color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-vivid-cyan-blue-color{color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-purple-color{color: var(--wp--preset--color--vivid-purple) !important;}.has-black-background-color{background-color: var(--wp--preset--color--black) !important;}.has-cyan-bluish-gray-background-color{background-color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-white-background-color{background-color: var(--wp--preset--color--white) !important;}.has-pale-pink-background-color{background-color: var(--wp--preset--color--pale-pink) !important;}.has-vivid-red-background-color{background-color: var(--wp--preset--color--vivid-red) !important;}.has-luminous-vivid-orange-background-color{background-color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-amber-background-color{background-color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-light-green-cyan-background-color{background-color: var(--wp--preset--color--light-green-cyan) !important;}.has-vivid-green-cyan-background-color{background-color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-pale-cyan-blue-background-color{background-color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-vivid-cyan-blue-background-color{background-color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-purple-background-color{background-color: var(--wp--preset--color--vivid-purple) !important;}.has-black-border-color{border-color: var(--wp--preset--color--black) !important;}.has-cyan-bluish-gray-border-color{border-color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-white-border-color{border-color: var(--wp--preset--color--white) !important;}.has-pale-pink-border-color{border-color: var(--wp--preset--color--pale-pink) !important;}.has-vivid-red-border-color{border-color: var(--wp--preset--color--vivid-red) !important;}.has-luminous-vivid-orange-border-color{border-color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-amber-border-color{border-color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-light-green-cyan-border-color{border-color: var(--wp--preset--color--light-green-cyan) !important;}.has-vivid-green-cyan-border-color{border-color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-pale-cyan-blue-border-color{border-color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-vivid-cyan-blue-border-color{border-color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-purple-border-color{border-color: var(--wp--preset--color--vivid-purple) !important;}.has-vivid-cyan-blue-to-vivid-purple-gradient-background{background: var(--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple) !important;}.has-light-green-cyan-to-vivid-green-cyan-gradient-background{background: var(--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan) !important;}.has-luminous-vivid-amber-to-luminous-vivid-orange-gradient-background{background: var(--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange) !important;}.has-luminous-vivid-orange-to-vivid-red-gradient-background{background: var(--wp--preset--gradient--luminous-vivid-orange-to-vivid-red) !important;}.has-very-light-gray-to-cyan-bluish-gray-gradient-background{background: var(--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray) !important;}.has-cool-to-warm-spectrum-gradient-background{background: var(--wp--preset--gradient--cool-to-warm-spectrum) !important;}.has-blush-light-purple-gradient-background{background: var(--wp--preset--gradient--blush-light-purple) !important;}.has-blush-bordeaux-gradient-background{background: var(--wp--preset--gradient--blush-bordeaux) !important;}.has-luminous-dusk-gradient-background{background: var(--wp--preset--gradient--luminous-dusk) !important;}.has-pale-ocean-gradient-background{background: var(--wp--preset--gradient--pale-ocean) !important;}.has-electric-grass-gradient-background{background: var(--wp--preset--gradient--electric-grass) !important;}.has-midnight-gradient-background{background: var(--wp--preset--gradient--midnight) !important;}.has-small-font-size{font-size: var(--wp--preset--font-size--small) !important;}.has-medium-font-size{font-size: var(--wp--preset--font-size--medium) !important;}.has-large-font-size{font-size: var(--wp--preset--font-size--large) !important;}.has-x-large-font-size{font-size: var(--wp--preset--font-size--x-large) !important;}
:where(.wp-block-post-template.is-layout-flex){gap: 1.25em;}:where(.wp-block-post-template.is-layout-grid){gap: 1.25em;}
:where(.wp-block-columns.is-layout-flex){gap: 2em;}:where(.wp-block-columns.is-layout-grid){gap: 2em;}
:root :where(.wp-block-pullquote){font-size: 1.5em;line-height: 1.6;}
</style>
<link rel='stylesheet' id='contact-form-7-css' href='../wp-content/plugins/contact-form-7/includes/css/styles5697.css?ver=5.5.3' type='text/css' media='all' />
<link rel='stylesheet' id='newsletter-css' href='../wp-content/plugins/newsletter/styleba31.css?ver=7.3.3' type='text/css' media='all' />
<link rel='stylesheet' id='js_composer_front-css' href='../wp-content/plugins/composer/assets/css/js_composer.min7263.css?ver=5.4.4' type='text/css' media='all' />
<script type="text/javascript" src="../wp-includes/js/jquery/jquery.minf43b.js?ver=3.7.1" id="jquery-core-js"></script>
<script type="text/javascript" src="../wp-includes/js/jquery/jquery-migrate.min5589.js?ver=3.4.1" id="jquery-migrate-js"></script>
<link rel="https://api.w.org/" href="../wp-json/index.php" /><link rel="alternate" title="JSON" type="application/json" href="../wp-json/wp/v2/pages/600" /><link rel="EditURI" type="application/rsd+xml" title="RSD" href="../xmlrpc0db0.php?rsd" />
<meta name="generator" content="WordPress 6.7.5" />
<link rel='shortlink' href='../index2b8b.php?p=600' />
<link rel="alternate" title="oEmbed (JSON)" type="application/json+oembed" href="../wp-json/oembed/1.0/embedd34b.json?url=https%3A%2F%2Fgoringmedicalprojects.com%2Fblog%2F" />
<link rel="alternate" title="oEmbed (XML)" type="text/xml+oembed" href="../wp-json/oembed/1.0/embedc2de?url=https%3A%2F%2Fgoringmedicalprojects.com%2Fblog%2F&amp;format=xml" />
<meta name="generator" content="Powered by WPBakery Page Builder - drag and drop page builder for WordPress."/>
<!--[if lte IE 9]><link rel="stylesheet" type="text/css" href="https://goringmedicalprojects.com/wp-content/plugins/composer/assets/css/vc_lte_ie9.min.css" media="screen"><![endif]--><style type="text/css" data-type="vc_shortcodes-custom-css">.vc_custom_1612870393946{margin-top: 0px !important;margin-bottom: 0px !important;padding-top: 0px !important;padding-bottom: 0px !important;}.vc_custom_1605881621898{margin-top: 0px !important;margin-bottom: 0px !important;padding-top: 0px !important;padding-bottom: 0px !important;}.vc_custom_1605881634762{margin-top: 0px !important;margin-bottom: 0px !important;padding-top: 0px !important;padding-bottom: 0px !important;}</style><noscript><style type="text/css"> .wpb_animate_when_almost_visible { opacity: 1; }</style></noscript>		
		<meta name="google-site-verification" content="cbsGP5B99j-IeRU774Y_uddg6K0KKY8piB4HFsX93eg" />
	</head>
	<body class="page-template-default page page-id-600 wpb-js-composer js-comp-ver-5.4.4 vc_responsive" >
		<!-- Wrapper Start -->
				<div id="preloader" class="load">
<video autoplay="autoplay" muted="" playsinline="">
<source src="../wp-content/uploads/2020/11/Wayne_Goring_Logo_Opt2_Final_Cut.mp4" type="video/mp4">Your browser does not support the video tag.</video>
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
									<div class="menu-main-menu-container"><ul id="menu-main-menu" class="nav navbar-nav"><li id="menu-item-23" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-home menu-item-23"><a title="Home" href="../index.php">Home</a></li>
<li id="menu-item-22" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-22"><a title="About Us" href="../about-us/index.php">About Us</a></li>
<li id="menu-item-21" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-21"><a title="Services" href="../services/index.php">Services</a></li>
<li id="menu-item-20" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-20"><a title="Testimonials" href="../testimonials/index.php">Testimonials</a></li>
<li id="menu-item-275" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-275"><a title="Project Gallery" href="../my-projects/index.php">Project Gallery</a></li>
<li id="menu-item-19" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-19"><a title="Case Studies" href="../case-studies/index.php">Case Studies</a></li>
<li id="menu-item-602" class="menu-item menu-item-type-post_type menu-item-object-page current-menu-item page_item page-item-600 current_page_item menu-item-602 active"><a title="Blog" href="index.php">Blog</a></li>
<li id="menu-item-18" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-18"><a title="Contact Us" href="../contact-us/index.php">Contact Us</a></li>
</ul></div>								</div>
							</div>
						</div>

						<div class="col-md-3 col-md-offset-1 Search-col">
							<div class="search-box">
																<div class="widget"><form role="search" method="get" id="searchform" class="searchform" action="https://goringmedicalprojects.com/">
				<div>
					<label class="screen-reader-text" for="s">Search for:</label>
					<input type="text" value="" name="s" id="s" />
					<input type="submit" id="searchsubmit" value="Search" />
				</div>
			</form></div>															</div>
						</div>
					</div>
				</div>
			</header>
			<div class="resnav-collapse" id="resnav">
				<button type="button" data-id="#resnav" data-action="nav-close" class="resnav-close"><i class="fa fa-times"></i></button>
				<div class="resnav-area">
					<div class="resnav-search-box">
												<div class="widget"><form role="search" method="get" id="searchform" class="searchform" action="https://goringmedicalprojects.com/">
				<div>
					<label class="screen-reader-text" for="s">Search for:</label>
					<input type="text" value="" name="s" id="s" />
					<input type="submit" id="searchsubmit" value="Search" />
				</div>
			</form></div>											</div>
					<div class="resnav-nav-menu-box">
						<ul id="menu-main-menu-1" class="list-unstyled mb-0"><li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-home menu-item-23"><a title="Home" href="../index.php">Home</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-22"><a title="About Us" href="../about-us/index.php">About Us</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-21"><a title="Services" href="../services/index.php">Services</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-20"><a title="Testimonials" href="../testimonials/index.php">Testimonials</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-275"><a title="Project Gallery" href="../my-projects/index.php">Project Gallery</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-19"><a title="Case Studies" href="../case-studies/index.php">Case Studies</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page current-menu-item page_item page-item-600 current_page_item menu-item-602 active"><a title="Blog" href="index.php">Blog</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-18"><a title="Contact Us" href="../contact-us/index.php">Contact Us</a></li>
</ul>					</div>
					<div class="resnav-contact-details">
						<ul class="list-unstyled mb-0">
							<li><strong>Phone:</strong> 
								<span><a href="tel:+44 (0) 7584 598 649">+44 (0) 7584 598 649</a></span>
							</li>
							<li><strong>Email:</strong>
								<span><a href="mailto:info@goringmedicalprojects.com">info@goringmedicalprojects.com</a></span>
							</li>
							<li><strong>Address:</strong>
								<span><p>Goring Medical Projects Ltd, 1 Trinity Farm Cottages, Barnby Moor, Retford, Notts, UK, DN22 8QW</p></span>
							</li>
						</ul>
					</div>
					<div class="resnav-social-links">
						<ul class="list-unstyled mb-0">
							<li><a href="https://www.facebook.com/Goring-Medical-Projects-100270068728705/"  target="_blank"><i class="fa fa-facebook-f"></i></a></li>
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

		<h1 class="banner-title page-title">Blog</h1>

	</div>

</section>

<section class="master_page">

	<div class="container">

		<div class="vc_row wpb_row vc_row-fluid"><div class="wpb_column vc_column_container vc_col-sm-12"><div class="vc_column-inner "><div class="wpb_wrapper">
	<div class="wpb_text_column wpb_content_element " >
		<div class="wpb_wrapper">
			<link rel='stylesheet' id='ff-dynamic-css1' type='text/css' href='../wp-content/resources/flow-flow/css/stream-id110ea.css?ver=1612870527'/><!-- Flow-Flow — Social stream plugin for WordPress -->
<div class="ff-stream" data-plugin="flow_flow" id="ff-stream-1"><span class="ff-loader"><span class="ff-square" ></span><span class="ff-square"></span><span class="ff-square ff-last"></span><span class="ff-square ff-clear"></span><span class="ff-square"></span><span class="ff-square ff-last"></span><span class="ff-square ff-clear"></span><span class="ff-square"></span><span class="ff-square ff-last"></span></span></div>
<script type="text/javascript">
(function ( $ ) {
    "use strict";

    var hash = '1680876008.6ed39cbf8acd763f9e230a9bfb703e5c1d1a0523fb1e64880ee0b7bc13bc16a687552765040e355d8b674ed00f415f09';

    var opts = window.FlowFlowOpts || {"streams":{},"open_in_new":"yep","filter_all":"All","filter_search":"Search","expand_text":"Expand","collapse_text":"Collapse","posted_on":"Posted on","followers":"Followers","following":"Following","posts":"Posts","show_more":"Show more","date_style":"agoStyleDate","dates":{"Yesterday":"Yesterday","s":"s","m":"m","h":"h","ago":"ago","months":["Jan","Feb","March","April","May","June","July","Aug","Sept","Oct","Nov","Dec"]},"lightbox_navigate":"Navigate with arrow keys","view_on":"View on","view_on_site":"View on site","view_all":"View all","comments":"comments","scroll":"Scroll for more","no_comments":"No comments yet.","be_first":"Be the first!","server_time":1786026968,"forceHTTPS":"nope","isAdmin":false,"ajaxurl":"https:\/\/goringmedicalprojects.com\/wp-admin\/admin-ajax.php","isLog":false,"plugin_base":"https:\/\/goringmedicalprojects.com\/wp-content\/plugins\/flow-flow","plugin_ver":"4.6.17"};

    var isLS = isLocalStorageNameSupported();

    var FF_resource = window.FF_resource ||
        {
            scriptDeferred: $.Deferred(),
            styleDeferred:  $.Deferred(),
            scriptLoading: false,
            styleLoading: false
        };

    if (!window.FF_resource) window.FF_resource = FF_resource;
    if (!window.FlowFlowOpts) window.FlowFlowOpts = opts;

    var data = {
        'action': 'fetch_posts',
        'stream-id': '1',
        'disable-cache': '',
        'hash': hash,
        'page': '0',
        'preview': '0'
    };

    var isMobile = /android|blackBerry|iphone|ipad|ipod|opera mini|iemobile/i.test( navigator.userAgent );

    var streamOpts = {"cloud":"nope","mod":"nope","order":"compareByTime","posts":"30","page-posts":"15","cache":"yep","cache_lifetime":"10","gallery":"nope","gallery-type":"classic","private":"nope","hide-on-desktop":"nope","hide-on-mobile":"nope","max-res":"nope","show-only-media-posts":"nope","titles":"nope","hidemeta":"nope","hidetext":"nope","headingcolor":"rgb(59, 61, 64)","subheadingcolor":"rgb(114, 112, 114)","hhalign":"center","bgcolor":"rgb(240, 240, 240)","filter":"nope","filtercolor":"rgb(205, 205, 205)","mobileslider":"nope","viewportin":"yep","width":"260","margin":"20","layout":"masonry","theme":"classic","gc-style":"style-1","upic-pos":"centered-big","upic-style":"round","bradius":"15","icon-style":"label2","cardcolor":"rgb(255, 255, 255)","namecolor":"rgb(59, 61, 64)","textcolor":"rgb(131, 141, 143)","linkscolor":"rgb(94, 159, 202)","restcolor":"rgb(132, 118, 129)","shadow":"rgba(0, 0, 0, 0.05)","bcolor":"rgba(0, 0, 0, 0.75)","talign":"left","icons-style":"outline","cards-num":"3","scrolltop":"yep","c-desktop":"5","c-laptop":"4","c-tablet-l":"3","c-tablet-p":"2","c-smart-l":"2","c-smart-p":"1","s-desktop":"15","s-laptop":"15","s-tablet-l":"10","s-tablet-p":"10","s-smart-l":"15","s-smart-p":"15","m-c-desktop":"5","m-c-laptop":"4","m-c-tablet-l":"3","m-c-tablet-p":"2","m-c-smart-l":"2","m-c-smart-p":"1","m-s-desktop":"15","m-s-laptop":"15","m-s-tablet-l":"10","m-s-tablet-p":"10","m-s-smart-l":"15","m-s-smart-p":"15","j-h-desktop":"260","j-h-laptop":"240","j-h-tablet-l":"220","j-h-tablet-p":"200","j-h-smart-l":"180","j-h-smart-p":"160","j-s-desktop":"0","j-s-laptop":"0","j-s-tablet-l":"0","j-s-tablet-p":"0","j-s-smart-l":"0","j-s-smart-p":"0","c-r-desktop":"2","c-r-laptop":"2","c-r-tablet-l":"2","c-r-tablet-p":"2","c-r-smart-l":"2","c-r-smart-p":"2","c-c-desktop":"5","c-c-laptop":"4","c-c-tablet-l":"3","c-c-tablet-p":"3","c-c-smart-l":"2","c-c-smart-p":"2","c-s-desktop":"0","c-s-laptop":"0","c-s-tablet-l":"0","c-s-tablet-p":"0","c-s-smart-l":"0","c-s-smart-p":"0","c-arrows-always":"yep","c-arrows-mob":"nope","c-dots":"yep","c-dots-mob":"nope","wallvm":"20","wallhm":"0","wallcomments":"yep","g-ratio-w":"1","g-ratio-h":"2","g-ratio-img":"1\/2","g-overlay":"nope","m-overlay":"nope","css":"div#ff-stream-1 {\n    background: transparent !important;\n}","template":["image","header","text","meta"],"tv":"nope","tv-int":"5","big":"nope","id":"1","last_changes":1612870527,"status":"1","feeds":[{"id":"sq82896","errors":[],"status":"1","enabled":"yep","last_update":"Aug 6 14:10","cache_lifetime":"60","system_enabled":"1","boosted":"nope","timeline-type":"page_timeline","content":"Goring Medical Projects","posts":"10","mod":"nope","type":"facebook","include":"","filter-by-words":"","page":"1"}],"preview":false};
    streamOpts.plugin = 'flow_flow';
    streamOpts.trueLayout = streamOpts.layout;

    /*we will modify 'grid' layout to get 'carousel' layout*/
    if ( streamOpts.layout == 'carousel' ) {
        streamOpts['layout'] = 'grid';
        streamOpts['g-ratio-h'] = "1";
        streamOpts['g-ratio-img'] = "1/2";
        streamOpts['g-ratio-w'] = "1";
        streamOpts['g-overlay'] = "yep";
        streamOpts['c-overlay'] = "yep";
        streamOpts['s-desktop'] = "0";
        streamOpts['s-laptop'] = "0";
        streamOpts['s-smart-l'] = "0";
        streamOpts['s-smart-p'] = "0";
        streamOpts['s-tablet-l'] = "0";
        streamOpts['s-tablet-p'] = "0";
    }
    else if ( streamOpts.layout == 'list' ) {  /*the same with list, we only need news feed style*/
        streamOpts['layout'] = 'masonry';
    }

    opts.streams['stream' + streamOpts.id] = streamOpts;
    var $cont = $("[data-plugin='flow_flow']#ff-stream-"+data['stream-id']);
    var ajaxDeferred;
    var script, style;
    var layout_pre = streamOpts.layout.charAt(0);
    var isOverlay = layout_pre === 'j' || streamOpts[layout_pre + '-overlay'] === 'yep' && streamOpts.trueLayout !== 'list';
    var imgIndex;
    if (isOverlay) {
        if (streamOpts.template[0] !== 'image') {
            for (var i = 0, len = streamOpts.template.length; i < len; i++) {
                if (streamOpts.template[i] === 'image') imgIndex = i;
            }
            streamOpts.template.splice(0, 0, streamOpts.template.splice(imgIndex, 1)[0]);
        }
        streamOpts.isOverlay = true;
    };
    if (FF_resource.scriptDeferred.state() === 'pending' && !FF_resource.scriptLoading) {
        script = document.createElement('script');
        script.src = "../wp-content/plugins/flow-flow/js/public.js";
        script.onload = function( script, textStatus ) {
            FF_resource.scriptDeferred.resolve();
        };
        document.body.appendChild(script);
        FF_resource.scriptLoading = true;
    };
    if (FF_resource.styleDeferred.state() === 'pending' && !FF_resource.styleLoading) {
        style = document.createElement('link');
        style.type = "text/css";
        style.id = "ff_style";
        style.rel = "stylesheet";
        style.href = "../wp-content/plugins/flow-flow/css/public.css";
        style.media = "screen";
        style.onload = function( script, textStatus ) {
            FF_resource.styleDeferred.resolve();
        };
        document.getElementsByTagName("head")[0].appendChild(style);
        FF_resource.styleLoading = true;
    }
    $cont.addClass('ff-layout-' + streamOpts.trueLayout);
    if (!isMobile && streamOpts.trueLayout !== 'carousel') $cont.css('minHeight', '500px');
    ajaxDeferred = isLS && sessionStorage.getItem(hash) ? {} : $.get(opts.ajaxurl, data)
;
    $.when( ajaxDeferred, FF_resource.scriptDeferred, FF_resource.styleDeferred ).done(function ( data ) {
        var response, $errCont, err;
        var moderation = 0;
        var original = (isLS && sessionStorage.getItem(hash)) ? JSON.parse( sessionStorage.getItem(hash) ) : data[0];
        try {
            // response = JSON.parse(original);
            response = original; // since 4.1
        } catch (e) {
            window.console && window.console.log('Flow-Flow gets invalid data from server');
            if (opts.isAdmin || opts.isLog) {
                $errCont = $('<div class="ff-errors" id="ff-errors-invalid-response"><div class="ff-disclaim">If you see this message then you have administrator permissions and Flow-Flow got invalid data from server. Please provide error message below if you are doing support request.<\/div><div class="ff-err-info"><\/div><\/div>');
                $cont.before($errCont);
                $errCont.find('.ff-err-info').html(original == '' ? 'Empty response from server' : original);
            }
            return;
        }
        opts.streams['stream' + streamOpts.id]['items'] = response;
        if (!FlowFlowOpts.dependencies) FlowFlowOpts.dependencies = {};
                var requests = [];
        var request, extension, style;

        for ( extension in FlowFlowOpts.dependencies ) {
            if ( FlowFlowOpts.dependencies[extension] && FlowFlowOpts.dependencies[extension] !== 'loaded') {
                request = $.getScript( opts.plugin_base + '-' + extension + '/js/ff_' + extension + '_public.js');
                requests.push(request);

                style = document.createElement('link');
                style.type = "text/css";
                style.rel = "stylesheet";
                style.id = "ff_ad_style";
                style.href = opts.plugin_base + '-' + extension + '/css/ff_' + extension + '_public.css';
                style.media = "screen";
                document.getElementsByTagName("head")[0].appendChild(style);

                FlowFlowOpts.dependencies[extension] = 'loaded';
            }
        }

        var resourcesLoaded = $.when.apply($, requests);

        resourcesLoaded.done(function(){
            var $stream, width;
            console.log(response);

            $stream = FlowFlow.buildStreamWith(response, streamOpts, moderation, FlowFlowOpts.dependencies);

            if (isLS && response.items.length > 0 && response.hash.length > 0) sessionStorage.setItem( JSON.stringify( response.hash ), original);

            var num = streamOpts.layout === 'compact' || (streamOpts.mobileslider === 'yep' && isMobile)? (streamOpts.mobileslider === 'yep' ? 3 : streamOpts['cards-num']) : false;

            $cont.append( $stream );

            if ( typeof $stream !== 'string' ) {
                FlowFlow.setupGrid($cont.find('.ff-stream-wrapper'), num, streamOpts.scrolltop === 'yep', streamOpts.gallery === 'yep', streamOpts, $cont);
            }

            setTimeout(function(){
                $cont.find('.ff-header').removeClass('ff-loading').end().find('.ff-loader').addClass('ff-squeezed').delay(300).hide();
            }, 0);

            
        }).fail(function(){
            console.log('Flow-Flow: resource loading failed');
        });

        var isErr = response.status === "errors";
        if ((opts.isAdmin || opts.isLog) && isErr) {
            $errCont = $('<div class="ff-errors"><div class="ff-err-info">If you see this then you are administrator and Flow-Flow got errors from APIs while requesting data. Please go to plugin admin and after refreshing page check for error(s) on stream settings page. Please provide error message info if you are doing support request.<\/div><\/div>');
            $cont.before($errCont);
        }

        if (opts.isAdmin && response.status === 'building') {
            window.console && window.console.log(response);
            $cont.prepend($('<div id="ff-admin-info">ADMIN INFO: Feeds cache is being built in background. Please wait for changes to apply. Page reload is required.<\/div>'));
        }
    });

    function isLocalStorageNameSupported() {
        var testKey = 'test', storage = window.sessionStorage;
        try {
            storage.setItem(testKey, '1');
            storage.removeItem(testKey);
            return true;
        } catch (error) {
            return false;
        }
    };

    return false;
}(jQuery));
</script>
<!-- Flow-Flow — Social streams plugin for Wordpress -->

		</div>
	</div>
</div></div></div></div>

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
						<div class="menu-main-menu-container"><ul id="menu-main-menu-2" class=""><li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-home menu-item-23"><a href="../index.php">Home</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-22"><a href="../about-us/index.php">About Us</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-21"><a href="../services/index.php">Services</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-20"><a href="../testimonials/index.php">Testimonials</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-275"><a href="../my-projects/index.php">Project Gallery</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-19"><a href="../case-studies/index.php">Case Studies</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page current-menu-item page_item page-item-600 current_page_item menu-item-602"><a href="index.php" aria-current="page">Blog</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-18"><a href="../contact-us/index.php">Contact Us</a></li>
</ul></div>					</div>	
					<div class="col-md-5 col-lg-5 col-sm-5 footLinks">
						<h4>Services Links</h4>
						<div class="menu-services-menu-container"><ul id="menu-services-menu" class=""><li id="menu-item-127" class="menu-item menu-item-type-post_type menu-item-object-service menu-item-127"><a href="../service/project-management/index.php">Project management</a></li>
<li id="menu-item-128" class="menu-item menu-item-type-post_type menu-item-object-service menu-item-128"><a href="../service/system-inspection-and-audit/index.php">QA, system inspection, <br>  evaluation &#038; reporting <br> (MRI PDF &#038; CT PDF)</a></li>
<li id="menu-item-129" class="menu-item menu-item-type-post_type menu-item-object-service menu-item-129"><a href="../service/system-installation/index.php">Medical equipment deinstallation, installation &#038; relocation</a></li>
<li id="menu-item-130" class="menu-item menu-item-type-post_type menu-item-object-service menu-item-130"><a href="../service/system-ramp-down/index.php">Ramp down/shimming</a></li>
<li id="menu-item-131" class="menu-item menu-item-type-post_type menu-item-object-service menu-item-131"><a href="../service/mobile-relocatable-design/index.php">Mobile, modular and relocatable design</a></li>
<li id="menu-item-132" class="menu-item menu-item-type-post_type menu-item-object-service menu-item-132"><a href="../service/patient-data-deletion/index.php">Blancco certified data deletion</a></li>
<li id="menu-item-568" class="menu-item menu-item-type-post_type menu-item-object-service menu-item-568"><a href="../service/helium-leak-detector-and-cold-head-exchange/index.php">Helium leak detection, coldhead exchange, repairs and rigging tool rental</a></li>
</ul></div>					</div>
					<div class="col-md-3 col-lg-3 col-sm-3 footLinks">
						<h4>Legals</h4>
						<div class="menu-legal-menu-container"><ul id="menu-legal-menu" class=""><li id="menu-item-519" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-519"><a href="../privacy-policy/index.php">Privacy Policy</a></li>
<li id="menu-item-518" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-518"><a href="../terms-conditions/index.php">Terms &#038; Conditions</a></li>
</ul></div>					</div>
				</div>
			</div>
			<div class="col-md-3 footContent">
				<div class="footer-bio">
					<h4>SIGN UP FOR NEWSLETTER</h4>
					<div class="newsletter">
												<div class="widget"><div class="tnp tnp-widget"><form method="post" action="https://goringmedicalprojects.com/?na=s">

<input type="hidden" name="nr" value="widget"><input type="hidden" name="nlang" value=""><div class="tnp-field tnp-field-email"><label for="tnp-1">Email</label>
<input class="tnp-email" type="email" name="ne" id="tnp-1" value="" required></div>
<div class="tnp-field tnp-field-button"><input class="tnp-submit" type="submit" value="Subscribe" >
</div>
</form>
</div></div>											</div>
				</div>
			</div>
		</div>
	</div>
	<div class="copyright">
		<p>© Copyright, Goring  Medical Projects Ltd, All Rights Reserved.</p>
	</div>
</footer>
</div> <!--Wrapper End-->


<!-- Bootstrap core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<script type="text/javascript" src="../wp-includes/js/dist/vendor/wp-polyfill.min2c7c.js?ver=3.15.0" id="wp-polyfill-js"></script>
<script type="text/javascript" id="contact-form-7-js-extra">
/* <![CDATA[ */
var wpcf7 = {"api":{"root":"https:\/\/goringmedicalprojects.com\/wp-json\/","namespace":"contact-form-7\/v1"}};
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

<!-- Mirrored from goringmedicalprojects.com/blog/ by HTTrack Website Copier/3.x [XR&CO'2014], Thu, 06 Aug 2026 14:38:12 GMT -->
</html>

<?php ob_end_flush(); ?>
