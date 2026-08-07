<?php
include('adminsession.php');
include_once 'config.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/admin_ui.php';
require_once __DIR__ . '/auth.php';

pih_admin_require_super_admin();

$db = isset($conn) && $conn instanceof mysqli ? $conn : null;
if ($db instanceof mysqli) {
    pih_admin_ensure_login_schema($db);
}

$message = '';
$error = '';
$themeOptions = pih_get_frontend_theme_definitions();
$defaultThemeKey = 'xray_theme';
$baseColorKeys = ['primary', 'accent', 'surface', 'text', 'muted'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!($db instanceof mysqli)) {
        $error = 'Database connection is unavailable.';
    } elseif (!pih_admin_validate_csrf()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        if (isset($_POST['save_theme'])) {
            $siteTheme = isset($_POST['site_theme']) ? trim((string) $_POST['site_theme']) : '';
            if (!array_key_exists($siteTheme, $themeOptions)) {
                $siteTheme = $defaultThemeKey;
            }

            $settingsBeforeSave = pih_get_settings_map($db);
            $paletteMap = pih_get_theme_palettes_map($settingsBeforeSave, $siteTheme);
            if (!isset($paletteMap[$siteTheme]) || !is_array($paletteMap[$siteTheme])) {
                $paletteMap[$siteTheme] = pih_get_theme_default_palette($siteTheme, $themeOptions);
            }

            $themePrimary = trim((string) ($_POST['theme_primary'] ?? '#0f4c81'));
            $themeAccent = trim((string) ($_POST['theme_accent'] ?? '#1f7cc1'));
            $themeSurface = trim((string) ($_POST['theme_surface'] ?? '#f6fbff'));
            $themeText = trim((string) ($_POST['theme_text'] ?? '#102a43'));
            $themeMuted = trim((string) ($_POST['theme_muted'] ?? '#486581'));

            $hexPattern = '/^#[0-9a-fA-F]{6}$/';
            $themePrimary = preg_match($hexPattern, $themePrimary) ? $themePrimary : '#0f4c81';
            $themeAccent = preg_match($hexPattern, $themeAccent) ? $themeAccent : '#1f7cc1';
            $themeSurface = preg_match($hexPattern, $themeSurface) ? $themeSurface : '#f6fbff';
            $themeText = preg_match($hexPattern, $themeText) ? $themeText : '#102a43';
            $themeMuted = preg_match($hexPattern, $themeMuted) ? $themeMuted : '#486581';

            $fallbackPalette = pih_get_theme_default_palette($siteTheme, $themeOptions);
            $candidatePalette = [
                'primary' => $themePrimary,
                'accent' => $themeAccent,
                'surface' => $themeSurface,
                'text' => $themeText,
                'muted' => $themeMuted,
            ];

            $postedExtras = isset($_POST['theme_extra']) && is_array($_POST['theme_extra']) ? $_POST['theme_extra'] : [];
            foreach ($fallbackPalette as $paletteKey => $paletteDefault) {
                if (in_array((string) $paletteKey, $baseColorKeys, true)) {
                    continue;
                }

                $postedValue = isset($postedExtras[$paletteKey]) ? trim((string) $postedExtras[$paletteKey]) : '';
                $candidatePalette[$paletteKey] = $postedValue !== '' ? $postedValue : (string) $paletteDefault;
            }

            $paletteMap[$siteTheme] = pih_sanitize_theme_palette($candidatePalette, $fallbackPalette);

            pih_set_setting('site_theme', $siteTheme, 'string', $db);
            pih_set_setting('theme_palettes_json', json_encode($paletteMap, JSON_UNESCAPED_SLASHES), 'string', $db);

            // Keep legacy keys in sync for backward compatibility in older paths.
            pih_set_setting('theme_primary', (string) $paletteMap[$siteTheme]['primary'], 'string', $db);
            pih_set_setting('theme_accent', (string) $paletteMap[$siteTheme]['accent'], 'string', $db);
            pih_set_setting('theme_surface', (string) $paletteMap[$siteTheme]['surface'], 'string', $db);
            pih_set_setting('theme_text', (string) $paletteMap[$siteTheme]['text'], 'string', $db);
            pih_set_setting('theme_muted', (string) $paletteMap[$siteTheme]['muted'], 'string', $db);

            $message = 'Theme settings were saved successfully for ' . ($themeOptions[$siteTheme]['label'] ?? $siteTheme) . '.';
        } elseif (isset($_POST['save_timeout'])) {
            $timeoutMinutes = (int) ($_POST['admin_session_timeout_minutes'] ?? 30);
            if ($timeoutMinutes < 5) {
                $timeoutMinutes = 5;
            }
            if ($timeoutMinutes > 240) {
                $timeoutMinutes = 240;
            }

            pih_set_setting('admin_session_timeout_minutes', (string) $timeoutMinutes, 'int', $db);
            $message = 'Session timeout settings were saved successfully.';
        }
    }
}

$settings = pih_get_settings_map($db);
$selectedThemeKey = trim((string) ($settings['site_theme'] ?? $defaultThemeKey));
if (!array_key_exists($selectedThemeKey, $themeOptions)) {
    $selectedThemeKey = $defaultThemeKey;
}
$themePaletteMap = pih_get_theme_palettes_map($settings, $selectedThemeKey);
$selectedThemePalette = isset($themePaletteMap[$selectedThemeKey]) && is_array($themePaletteMap[$selectedThemeKey])
    ? $themePaletteMap[$selectedThemeKey]
    : pih_get_theme_default_palette($selectedThemeKey, $themeOptions);

$themeFieldMetaMap = [];
foreach ($themeOptions as $themeKey => $themeMeta) {
    $themeFieldMetaMap[(string) $themeKey] = pih_get_theme_color_field_definitions((string) $themeKey);
}

$allExtraKeyMeta = [];
foreach ($themeFieldMetaMap as $themeKey => $fieldMeta) {
    foreach ($fieldMeta as $fieldKey => $fieldDefinition) {
        if (in_array((string) $fieldKey, $baseColorKeys, true)) {
            continue;
        }

        if (!isset($allExtraKeyMeta[$fieldKey])) {
            $allExtraKeyMeta[$fieldKey] = [
                'label' => isset($fieldDefinition['label']) ? (string) $fieldDefinition['label'] : ucwords(str_replace('_', ' ', (string) $fieldKey)),
                'description' => isset($fieldDefinition['description']) ? (string) $fieldDefinition['description'] : '',
            ];
        }
    }
}

$selectedThemeFieldMeta = isset($themeFieldMetaMap[$selectedThemeKey]) && is_array($themeFieldMetaMap[$selectedThemeKey])
    ? $themeFieldMetaMap[$selectedThemeKey]
    : pih_get_theme_color_field_definitions($selectedThemeKey);

$siteBaseUrl = rtrim((string) ($settings['site_url'] ?? ''), '/');
$themeScreenshotMap = [];
foreach ($themeOptions as $themeKey => $themeMeta) {
    $screenshotRel = ltrim((string) ($themeMeta['screenshot'] ?? ('themes/' . $themeKey . '/screenshot.png')), '/');
    $themeScreenshotMap[(string) $themeKey] = ($siteBaseUrl !== '' ? $siteBaseUrl . '/' : '/') . $screenshotRel;
}

$selectedThemeScreenshot = isset($themeScreenshotMap[$selectedThemeKey])
    ? (string) $themeScreenshotMap[$selectedThemeKey]
    : '';

$currentTimeout = (int) ($settings['admin_session_timeout_minutes'] ?? 30);
if ($currentTimeout < 5) {
    $currentTimeout = 5;
}
if ($currentTimeout > 240) {
    $currentTimeout = 240;
}

pih_admin_render_start(
    'Theme and Timeout Settings',
    'Manage frontend theme colors and admin session timeout policy',
    'theme_settings',
    [
        ['href' => 'admin_users.php', 'icon' => 'icon-group', 'label' => 'Admin Users'],
        ['href' => 'smtp_settings.php', 'icon' => 'icon-envelope', 'label' => 'SMTP Settings'],
    ]
);
?>
<div class="module">
    <div class="module-head">
        <h3>Theme Settings</h3>
    </div>
    <div class="module-body">
        <style>
            .pih-color-row {
                align-items: center;
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }

            .pih-theme-description {
                color: #607080;
                display: block;
                font-size: 11px;
                line-height: 1.35;
                margin-top: 6px;
            }

            .pih-theme-preview {
                align-items: center;
                display: inline-flex;
                gap: 8px;
            }

            .pih-theme-preview .swatch {
                border: 1px solid #c8d0d8;
                border-radius: 4px;
                display: inline-block;
                height: 18px;
                width: 28px;
            }

            .pih-theme-preview .hex {
                color: #4f6071;
                font-size: 12px;
                font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
                letter-spacing: 0.02em;
            }

            .pih-theme-context-note {
                background: #f6f9fc;
                border: 1px solid #d6e0ea;
                border-radius: 4px;
                color: #345;
                margin-bottom: 16px;
                padding: 10px 12px;
            }

            .pih-theme-screenshot {
                background: #fbfdff;
                border: 1px solid #d8e2ec;
                border-radius: 6px;
                margin: 12px 0 18px;
                padding: 10px;
            }

            .pih-theme-screenshot-header {
                color: #2f465c;
                font-size: 12px;
                font-weight: 700;
                margin-bottom: 8px;
                text-transform: uppercase;
            }

            .pih-theme-screenshot img {
                background: #fff;
                border: 1px solid #d4dde7;
                border-radius: 4px;
                display: block;
                height: auto;
                max-height: 260px;
                object-fit: cover;
                width: 100%;
            }

            .pih-theme-screenshot-note {
                color: #607080;
                display: block;
                font-size: 11px;
                margin-top: 7px;
            }
        </style>

        <?php if ($message !== '') { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>
        <?php if ($error !== '') { ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>

        <div class="pih-theme-context-note" id="theme_context_note">
            Editing color roles for <strong id="theme_context_label"><?php echo htmlspecialchars((string) ($themeOptions[$selectedThemeKey]['label'] ?? $selectedThemeKey), ENT_QUOTES, 'UTF-8'); ?></strong>.
            Each role below targets theme-specific UI areas.
        </div>

        <form class="form-horizontal row-fluid" method="post" action="">
            <?php echo pih_admin_csrf_input(); ?>

            <div class="control-group">
                <label class="control-label">Frontend Template</label>
                <div class="controls">
                    <select class="span5" name="site_theme" id="site_theme_selector">
                        <?php foreach ($themeOptions as $themeKey => $themeMeta) { ?>
                            <option value="<?php echo htmlspecialchars((string) $themeKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedThemeKey === $themeKey ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) ($themeMeta['label'] ?? $themeKey), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label">Selected Theme Preview</label>
                <div class="controls">
                    <div class="pih-theme-screenshot">
                        <div class="pih-theme-screenshot-header" id="theme_screenshot_title"><?php echo htmlspecialchars((string) ($themeOptions[$selectedThemeKey]['label'] ?? $selectedThemeKey), ENT_QUOTES, 'UTF-8'); ?> Screenshot</div>
                        <img id="theme_screenshot_image" src="<?php echo htmlspecialchars($selectedThemeScreenshot, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) ($themeOptions[$selectedThemeKey]['label'] ?? $selectedThemeKey), ENT_QUOTES, 'UTF-8'); ?> screenshot" onerror="this.style.display='none';document.getElementById('theme_screenshot_missing').style.display='block';">
                        <div id="theme_screenshot_missing" class="pih-theme-screenshot-note" style="display:none;">No screenshot found yet. Add screenshot.png inside this theme root folder.</div>
                        <span class="pih-theme-screenshot-note">Preview source: screenshot.png in the selected theme root folder.</span>
                    </div>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" id="label_theme_primary"><?php echo htmlspecialchars((string) ($selectedThemeFieldMeta['primary']['label'] ?? 'Primary Color'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="controls">
                    <div class="pih-color-row">
                        <input class="span3" type="color" id="theme_primary" name="theme_primary" value="<?php echo htmlspecialchars((string) ($selectedThemePalette['primary'] ?? '#0f4c81'), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="pih-theme-preview"><span class="swatch" id="swatch_theme_primary" style="background: <?php echo htmlspecialchars((string) ($selectedThemePalette['primary'] ?? '#0f4c81'), ENT_QUOTES, 'UTF-8'); ?>;"></span><span class="hex" id="hex_theme_primary"><?php echo htmlspecialchars((string) ($selectedThemePalette['primary'] ?? '#0f4c81'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                    </div>
                    <span class="pih-theme-description" id="help_theme_primary"><?php echo htmlspecialchars((string) ($selectedThemeFieldMeta['primary']['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" id="label_theme_accent"><?php echo htmlspecialchars((string) ($selectedThemeFieldMeta['accent']['label'] ?? 'Accent Color'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="controls">
                    <div class="pih-color-row">
                        <input class="span3" type="color" id="theme_accent" name="theme_accent" value="<?php echo htmlspecialchars((string) ($selectedThemePalette['accent'] ?? '#1f7cc1'), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="pih-theme-preview"><span class="swatch" id="swatch_theme_accent" style="background: <?php echo htmlspecialchars((string) ($selectedThemePalette['accent'] ?? '#1f7cc1'), ENT_QUOTES, 'UTF-8'); ?>;"></span><span class="hex" id="hex_theme_accent"><?php echo htmlspecialchars((string) ($selectedThemePalette['accent'] ?? '#1f7cc1'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                    </div>
                    <span class="pih-theme-description" id="help_theme_accent"><?php echo htmlspecialchars((string) ($selectedThemeFieldMeta['accent']['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" id="label_theme_surface"><?php echo htmlspecialchars((string) ($selectedThemeFieldMeta['surface']['label'] ?? 'Surface Color'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="controls">
                    <div class="pih-color-row">
                        <input class="span3" type="color" id="theme_surface" name="theme_surface" value="<?php echo htmlspecialchars((string) ($selectedThemePalette['surface'] ?? '#f6fbff'), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="pih-theme-preview"><span class="swatch" id="swatch_theme_surface" style="background: <?php echo htmlspecialchars((string) ($selectedThemePalette['surface'] ?? '#f6fbff'), ENT_QUOTES, 'UTF-8'); ?>;"></span><span class="hex" id="hex_theme_surface"><?php echo htmlspecialchars((string) ($selectedThemePalette['surface'] ?? '#f6fbff'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                    </div>
                    <span class="pih-theme-description" id="help_theme_surface"><?php echo htmlspecialchars((string) ($selectedThemeFieldMeta['surface']['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" id="label_theme_text"><?php echo htmlspecialchars((string) ($selectedThemeFieldMeta['text']['label'] ?? 'Text Color'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="controls">
                    <div class="pih-color-row">
                        <input class="span3" type="color" id="theme_text" name="theme_text" value="<?php echo htmlspecialchars((string) ($selectedThemePalette['text'] ?? '#102a43'), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="pih-theme-preview"><span class="swatch" id="swatch_theme_text" style="background: <?php echo htmlspecialchars((string) ($selectedThemePalette['text'] ?? '#102a43'), ENT_QUOTES, 'UTF-8'); ?>;"></span><span class="hex" id="hex_theme_text"><?php echo htmlspecialchars((string) ($selectedThemePalette['text'] ?? '#102a43'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                    </div>
                    <span class="pih-theme-description" id="help_theme_text"><?php echo htmlspecialchars((string) ($selectedThemeFieldMeta['text']['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" id="label_theme_muted"><?php echo htmlspecialchars((string) ($selectedThemeFieldMeta['muted']['label'] ?? 'Muted Color'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="controls">
                    <div class="pih-color-row">
                        <input class="span3" type="color" id="theme_muted" name="theme_muted" value="<?php echo htmlspecialchars((string) ($selectedThemePalette['muted'] ?? '#486581'), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="pih-theme-preview"><span class="swatch" id="swatch_theme_muted" style="background: <?php echo htmlspecialchars((string) ($selectedThemePalette['muted'] ?? '#486581'), ENT_QUOTES, 'UTF-8'); ?>;"></span><span class="hex" id="hex_theme_muted"><?php echo htmlspecialchars((string) ($selectedThemePalette['muted'] ?? '#486581'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                    </div>
                    <span class="pih-theme-description" id="help_theme_muted"><?php echo htmlspecialchars((string) ($selectedThemeFieldMeta['muted']['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>

            <?php foreach ($allExtraKeyMeta as $extraKey => $extraMeta) {
                $extraLabel = isset($selectedThemeFieldMeta[$extraKey]['label']) ? (string) $selectedThemeFieldMeta[$extraKey]['label'] : (string) ($extraMeta['label'] ?? ucwords(str_replace('_', ' ', (string) $extraKey)));
                $extraDescription = isset($selectedThemeFieldMeta[$extraKey]['description']) ? (string) $selectedThemeFieldMeta[$extraKey]['description'] : (string) ($extraMeta['description'] ?? '');
                $extraValue = isset($selectedThemePalette[$extraKey]) ? (string) $selectedThemePalette[$extraKey] : '#000000';
                $isVisible = isset($selectedThemeFieldMeta[$extraKey]);
            ?>
            <div class="control-group" id="extra_row_<?php echo htmlspecialchars((string) $extraKey, ENT_QUOTES, 'UTF-8'); ?>" style="<?php echo $isVisible ? '' : 'display:none;'; ?>">
                <label class="control-label" id="label_theme_<?php echo htmlspecialchars((string) $extraKey, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($extraLabel, ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="controls">
                    <div class="pih-color-row">
                        <input class="span3" type="color" id="theme_<?php echo htmlspecialchars((string) $extraKey, ENT_QUOTES, 'UTF-8'); ?>" name="theme_extra[<?php echo htmlspecialchars((string) $extraKey, ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo htmlspecialchars($extraValue, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="pih-theme-preview"><span class="swatch" id="swatch_theme_<?php echo htmlspecialchars((string) $extraKey, ENT_QUOTES, 'UTF-8'); ?>" style="background: <?php echo htmlspecialchars($extraValue, ENT_QUOTES, 'UTF-8'); ?>;"></span><span class="hex" id="hex_theme_<?php echo htmlspecialchars((string) $extraKey, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(strtoupper($extraValue), ENT_QUOTES, 'UTF-8'); ?></span></div>
                    </div>
                    <span class="pih-theme-description" id="help_theme_<?php echo htmlspecialchars((string) $extraKey, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($extraDescription, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <?php } ?>

            <div class="control-group">
                <div class="controls">
                    <button type="submit" name="save_theme" value="1" class="btn btn-success">Save Theme Settings</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="module" style="margin-top: 20px;">
    <div class="module-head">
        <h3>Session Timeout</h3>
    </div>
    <div class="module-body">
        <form class="form-horizontal row-fluid" method="post" action="">
            <?php echo pih_admin_csrf_input(); ?>

            <div class="control-group">
                <label class="control-label">Idle Timeout (Minutes)</label>
                <div class="controls">
                    <input class="span2" type="number" min="5" max="240" step="1" name="admin_session_timeout_minutes" value="<?php echo (int) $currentTimeout; ?>">
                    <span class="help-inline">Allowed range: 5 to 240 minutes.</span>
                </div>
            </div>

            <div class="control-group">
                <div class="controls">
                    <button type="submit" name="save_timeout" value="1" class="btn btn-success">Save Timeout Settings</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
(function(){
    var selector = document.getElementById('site_theme_selector');
    if(!selector){ return; }

    var paletteMap = <?php echo json_encode($themePaletteMap, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var themeLabels = <?php echo json_encode(array_map(static function ($meta) { return (string) ($meta['label'] ?? 'Theme'); }, $themeOptions), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var fieldMetaMap = <?php echo json_encode($themeFieldMetaMap, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var screenshotMap = <?php echo json_encode($themeScreenshotMap, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var defaults = {
        primary: '#0f4c81',
        accent: '#1f7cc1',
        surface: '#f6fbff',
        text: '#102a43',
        muted: '#486581'
    };

    var baseKeys = ['primary','accent','surface','text','muted'];
    var extraKeys = <?php echo json_encode(array_values(array_keys($allExtraKeyMeta)), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var keys = baseKeys.concat(extraKeys);

    function updatePreview(key){
        var input = document.getElementById('theme_' + key);
        var swatch = document.getElementById('swatch_theme_' + key);
        var hex = document.getElementById('hex_theme_' + key);
        if(!input){ return; }

        if(swatch){
            swatch.style.background = input.value || defaults[key];
        }

        if(hex){
            hex.textContent = (input.value || defaults[key] || '').toUpperCase();
        }
    }

    function updateThemeContext(themeKey){
        var labelNode = document.getElementById('theme_context_label');
        if(labelNode){
            labelNode.textContent = themeLabels[themeKey] || themeKey;
        }
    }

    function updateThemeScreenshot(themeKey){
        var titleNode = document.getElementById('theme_screenshot_title');
        var imageNode = document.getElementById('theme_screenshot_image');
        var missingNode = document.getElementById('theme_screenshot_missing');
        var label = themeLabels[themeKey] || themeKey;
        var src = screenshotMap[themeKey] || '';

        if(titleNode){
            titleNode.textContent = label + ' Screenshot';
        }

        if(imageNode){
            imageNode.style.display = src ? 'block' : 'none';
            imageNode.alt = label + ' screenshot';
            if(src){
                imageNode.src = src;
            }
        }

        if(missingNode){
            missingNode.style.display = src ? 'none' : 'block';
        }
    }

    function applyFieldMeta(themeKey){
        var meta = fieldMetaMap[themeKey] || {};
        baseKeys.forEach(function(key){
            var labelNode = document.getElementById('label_theme_' + key);
            var helpNode = document.getElementById('help_theme_' + key);
            var role = meta[key] || {};

            if(labelNode){
                labelNode.textContent = role.label || (key.charAt(0).toUpperCase() + key.slice(1) + ' Color');
            }

            if(helpNode){
                helpNode.textContent = role.description || '';
            }
        });

        extraKeys.forEach(function(key){
            var rowNode = document.getElementById('extra_row_' + key);
            var labelNode = document.getElementById('label_theme_' + key);
            var helpNode = document.getElementById('help_theme_' + key);
            var inputNode = document.getElementById('theme_' + key);
            var role = meta[key] || null;

            if(!role){
                if(rowNode){
                    rowNode.style.display = 'none';
                }
                return;
            }

            if(rowNode){
                rowNode.style.display = '';
            }

            if(labelNode){
                labelNode.textContent = role.label || (key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ') + ' Color');
            }

            if(helpNode){
                helpNode.textContent = role.description || '';
            }

            if(inputNode && !inputNode.value){
                inputNode.value = '#000000';
            }
        });
    }

    function applyPalette(themeKey){
        if(!themeKey || !paletteMap[themeKey]){ return; }
        var p = paletteMap[themeKey];
        var meta = fieldMetaMap[themeKey] || {};
        keys.forEach(function(key){
            if(!meta[key] && extraKeys.indexOf(key) !== -1){
                return;
            }

            var input = document.getElementById('theme_' + key);
            if(input){
                var candidate = p[key] || defaults[key] || input.value || '#000000';
                input.value = candidate;
                updatePreview(key);
            }
        });
    }

    selector.addEventListener('change', function(){
        updateThemeContext(selector.value);
        updateThemeScreenshot(selector.value);
        applyFieldMeta(selector.value);
        applyPalette(selector.value);
    });

    keys.forEach(function(key){
        var input = document.getElementById('theme_' + key);
        if(input){
            input.addEventListener('input', function(){ updatePreview(key); });
            updatePreview(key);
        }
    });

    updateThemeContext(selector.value);
    updateThemeScreenshot(selector.value);
    applyFieldMeta(selector.value);
})();
</script>
<?php pih_admin_render_end(); ?>
