<?php
/**
 * MicroCalc — виджет калькулятора для MODX Revolution 2.8+ и 3.x.
 *
 * Вызов: [[microcalc? &id=`318` &agree=`1`]]
 * Параметры: &id (обязательно), &agree=`1` (согласие с условиями, без него виджет не выводится),
 * &lang (ru, en, fr; по умолчанию язык контекста), &theme (light, dark, auto), &accent, &bg, &text, &border, &link
 * (цвет hex), &radius, &size, &pad, &font, &head=`0`, &frame=`0`, &width (px), &base (адрес сервиса).
 *
 * HTML собирается на сервере (spec/embed.md, раздел 2): ссылку должен видеть поисковый робот.
 *
 * @package microcalc
 * @license GNU GPL v2 or later
 */

/** @var modX $modx */
/** @var array $scriptProperties */

// адрес сервиса по умолчанию: задаётся только здесь, меняется параметром &base
$microcalcBase = 'https://microcalc.io/';

$p = $scriptProperties;
$isManager = $modx->user && $modx->user->hasSessionContext('mgr');
$notice = function ($text) use ($isManager) {
    // подсказка только тем, кто вошёл в админку; посетители ничего не видят
    return $isManager ? '<p style="border:1px dashed #c52827;padding:8px 12px;color:#c52827">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p>' : '';
};

if (empty($p['agree'])) {
    return $notice('MicroCalc: добавьте &agree=`1` — согласие показывать открытую ссылку на сервис под виджетом. Без ссылки виджет не считает.');
}
$id = (int) ($p['id'] ?? 0);
if ($id <= 0) {
    return $notice('MicroCalc: укажите калькулятор, например [[microcalc? &id=`318` &agree=`1`]].');
}

$base = trim((string) ($p['base'] ?? ''));
$base = rtrim(($base !== '' && strpos($base, 'https://') === 0) ? $base : $microcalcBase, '/') . '/';

$allowed = ['ru', 'en', 'fr'];
$lang = (string) ($p['lang'] ?? '');
if (!in_array($lang, $allowed, true)) {
    $lang = substr((string) $modx->getOption('cultureKey'), 0, 2);
    $lang = in_array($lang, $allowed, true) ? $lang : 'en';
}

// каталог (spec/embed.md, раздел 1): кэш MODX на 12 часов и последняя удачная копия без срока
$cacheOpt = [xPDO::OPT_CACHE_KEY => 'microcalc'];
$key = 'catalog_' . $lang . '_' . md5($base);
$catalog = $modx->cacheManager->get($key, $cacheOpt);
if (!is_array($catalog)) {
    $body = false;
    $url = $base . 'calc/widget_catalog.php?v=1&lang=' . $lang;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_FOLLOWLOCATION => false]);
        $body = curl_exec($ch);
        if ((int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE) !== 200) {
            $body = false;
        }
        curl_close($ch);
    }
    $data = $body ? json_decode($body, true) : null;
    $fresh = null;
    $starts = function ($u) use ($base) {
        return is_string($u) && strpos($u, $base) === 0;
    };
    if (is_array($data) && (int) ($data['version'] ?? 0) === 1 && !empty($data['items']) && $starts($data['site']['script'] ?? '')) {
        $items = [];
        foreach ($data['items'] as $item) {
            if (isset($item['id'], $item['name']) && $starts($item['page_url'] ?? '') && $starts($item['widget_src'] ?? '')) {
                $items[(int) $item['id']] = [strip_tags((string) $item['name']), (string) $item['page_url'], (string) $item['widget_src']];
            }
        }
        $fresh = ['name' => strip_tags((string) ($data['site']['name'] ?? '')), 'script' => (string) $data['site']['script'], 'items' => $items];
    }
    if ($fresh) {
        $modx->cacheManager->set($key, $fresh, 43200, $cacheOpt);
        $modx->cacheManager->set($key . '_stale', $fresh, 0, $cacheOpt);
        $catalog = $fresh;
    } else {
        $catalog = $modx->cacheManager->get($key . '_stale', $cacheOpt);
        if (is_array($catalog)) {
            $modx->cacheManager->set($key, $catalog, 3600, $cacheOpt); // повторная попытка через час
        }
    }
}
if (!is_array($catalog)) {
    return $notice('MicroCalc: не удалось загрузить список калькуляторов.');
}
if (!isset($catalog['items'][$id])) {
    return $notice('MicroCalc: калькулятор не найден.');
}
list($name, $pageUrl, $widgetSrc) = $catalog['items'][$id];

// оформление (spec/embed.md, раздел 3): только допустимые значения и только отличия от умолчаний
$q = [];
if (in_array($p['theme'] ?? '', ['dark', 'auto'], true)) {
    $q['theme'] = $p['theme'];
}
foreach (['accent', 'bg', 'text', 'border', 'link'] as $k) {
    $v = strtolower(ltrim(trim((string) ($p[$k] ?? '')), '#'));
    if (preg_match('/^([0-9a-f]{3}|[0-9a-f]{6})$/', $v)) {
        $q[$k] = $v;
    }
}
foreach (['radius' => [0, 24, 6], 'size' => [12, 20, 16], 'pad' => [0, 40, 12]] as $k => $r) {
    $v = $p[$k] ?? '';
    if (is_numeric($v) && (int) $v >= $r[0] && (int) $v <= $r[1] && (int) $v !== $r[2]) {
        $q[$k] = (int) $v;
    }
}
if (in_array($p['font'] ?? '', ['inter', 'arial', 'verdana', 'tahoma', 'georgia', 'times', 'mono'], true)) {
    $q['font'] = $p['font'];
}
foreach (['head', 'frame'] as $k) {
    if (isset($p[$k]) && (string) $p[$k] === '0') {
        $q[$k] = 0;
    }
}
$q['src'] = 'modx';
$src = $widgetSrc . (strpos($widgetSrc, '?') === false ? '?' : '&') . http_build_query($q);
$width = max(0, (int) ($p['width'] ?? 0));
$e = function ($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};

// widget.js один на страницу: повторное подключение он тоже переносит, но лишний запрос не нужен
$modx->regClientScript('<script src="' . $e($catalog['script']) . '" async></script>', true);

return '<div class="calc-widget" data-src="' . $e($src) . '"' . ($width ? ' style="max-width:' . $width . 'px"' : '') . '></div>' . "\n"
    . '<p class="calc-widget-link"><a href="' . $e($pageUrl) . '">' . $e($name) . '</a> — ' . $e($catalog['name']) . '</p>';
