<?php

require_once __DIR__ . '/includes/settings.php';

$settings = pih_get_settings_map();

$normalize_multiline_setting = static function (string $value): string {
	$value = str_replace(["\\r\\n", "\\n", "\\r"], "\n", $value);
	$value = str_replace(["\r\n", "\r"], "\n", $value);
	return trim($value);
};

$name = trim((string) ($settings['site_name'] ?? 'Private Imaging healthcare Center'));
$names = trim((string) ($settings['site_short_name'] ?? 'VPH'));

$addr = $normalize_multiline_setting((string) ($settings['site_addr_primary'] ?? ''));
$addr2 = $normalize_multiline_setting((string) ($settings['site_addr_secondary'] ?? ''));

$phone = (string) ($settings['site_phone_primary'] ?? '');
$phone2 = (string) ($settings['site_phone_secondary'] ?? '');

$url = rtrim((string) ($settings['site_url'] ?? ''), '/');
$urlh = trim((string) ($settings['site_host'] ?? ''));

$email = (string) ($settings['site_email_primary'] ?? '');
$email2 = (string) ($settings['site_email_secondary'] ?? '');

$logo_path = ltrim((string) ($settings['site_logo_path'] ?? 'static/images/NY_Imaging_Specialists.png'), '/');
$favicon_path = ltrim((string) ($settings['site_favicon_path'] ?? 'static/images/mri.png'), '/');

$themeOverrideKey = '';
if (isset($GLOBALS['pih_theme_override']) && is_string($GLOBALS['pih_theme_override'])) {
	$themeOverrideKey = trim($GLOBALS['pih_theme_override']);
}

$site_theme_key = $themeOverrideKey !== '' ? $themeOverrideKey : (string) ($settings['site_theme'] ?? 'xray_theme');
$site_theme = pih_get_frontend_theme($site_theme_key);
$theme_css_path = ltrim((string) ($site_theme['stylesheet'] ?? ''), '/');
$theme_body_class = trim((string) ($site_theme['body_class'] ?? ''));
$theme_header_path = ltrim((string) ($site_theme['header'] ?? 'themes/xray_theme/header.php'), '/');
$theme_footer_path = ltrim((string) ($site_theme['footer'] ?? 'themes/xray_theme/footer.php'), '/');

$resolved_theme_key = isset($site_theme['key']) ? (string) $site_theme['key'] : $site_theme_key;
$theme_palette = pih_get_theme_palette_for_key($resolved_theme_key, $settings);

$theme_primary = (string) ($theme_palette['primary'] ?? '#0f4c81');
$theme_accent = (string) ($theme_palette['accent'] ?? '#1f7cc1');
$theme_surface = (string) ($theme_palette['surface'] ?? '#f6fbff');
$theme_text = (string) ($theme_palette['text'] ?? '#102a43');
$theme_muted = (string) ($theme_palette['muted'] ?? '#486581');

$theme_extra_colors = [];
foreach ($theme_palette as $palette_key => $palette_value) {
	if (in_array((string) $palette_key, ['primary', 'accent', 'surface', 'text', 'muted'], true)) {
		continue;
	}

	$theme_extra_colors[(string) $palette_key] = (string) $palette_value;
}

$tawk = (string) ($settings['livechat_script'] ?? '');
