# Формат виджета MicroCalc (версия 1)

Общая договорённость для всех плагинов. Сервер — сайт MicroCalc (по умолчанию `https://microcalc.io/`, в каждом плагине адрес задаётся в одном месте и меняется в настройках).

## 1. Каталог

```
GET {base}calc/widget_catalog.php?v=1&lang=ru
```

`lang` — `ru`, `en` или `fr`. Ответ JSON, `Access-Control-Allow-Origin: *`, `Cache-Control: public, max-age=3600`. Плагин хранит каталог у себя 12 часов и держит последнюю удачную копию на случай, если сервер недоступен.

```json
{
  "version": 1,
  "lang": "ru",
  "langs": ["ru", "en", "fr"],
  "site": {
    "name": "microcalc.io",
    "home": "https://microcalc.io/",
    "script": "https://microcalc.io/include/js/widget.js",
    "setup": "https://microcalc.io/ru/vidzhety.html",
    "check": "https://microcalc.io/calc/widget_check.php"
  },
  "style": {
    "defaults": {"theme": "light", "accent": "", "bg": "", "text": "", "border": "", "link": "",
                 "radius": 6, "font": "system", "size": 16, "pad": 12, "head": 1, "frame": 1},
    "themes": ["light", "dark", "auto"],
    "fonts": ["system", "inter", "arial", "verdana", "tahoma", "georgia", "times", "mono"]
  },
  "terms": ["Виджет бесплатный…", "…"],
  "groups": [{"id": 13, "name": "Алгебра"}],
  "items": [
    {"id": 318, "type": "calc", "group": 256, "name": "Давление",
     "page_url": "https://microcalc.io/ru/davlenie.html",
     "widget_src": "https://microcalc.io/widget/ru/davlenie.html",
     "icon": "https://microcalc.io/calc/fizika/davlenie/davlenie.svg",
     "popularity": 12345}
  ]
}
```

- `type` — `calc` (калькулятор или конвертер) или `trainer` (тренажёр).
- `items` отсортированы по популярности.
- Адреса с не-ASCII символами приходят закодированными (`%D0%B4…`).
- Плагин обязан проверить, что `widget_src`, `page_url` и `site.script` начинаются с настроенного адреса сайта, и иначе запись не выводить.
- Если поменяется формат, номер `version` увеличится. Плагин, который не знает новый номер, продолжает работать с последней удачной копией каталога.

## 2. Код на странице

Плагин выводит HTML **на сервере CMS**, а не скриптом. Без этого поисковый робот и проверка ссылки не увидят ссылку.

```html
<div class="calc-widget" data-src="{widget_src}?{оформление}&src={cms}" style="max-width:{ширина}px"></div>
<p class="calc-widget-link"><a href="{page_url}">{name}</a> — {site.name}</p>
<script src="{site.script}" async></script>
```

- `style` у блока выводится, только если задана ширина.
- Все значения экранируются средствами CMS (`esc_url`, `esc_attr`, `htmlspecialchars`).
- Ссылку нельзя выводить с `rel="nofollow"`, `sponsored`, `ugc`, внутри `<noindex>`, скрытой или созданной скриптом.
- `<script>` можно вывести один раз на страницу: если он подключён несколько раз, `widget.js` это учитывает.
- Имена `calc-widget`, `calc-widget-link`, сообщение `calc-widget:height` и `window.CalcWidget` менять нельзя.

## 3. Параметры оформления

Добавляются к `widget_src` только если отличаются от `style.defaults`. Сервер принимает только эти значения, остальное отбрасывает.

| Параметр | Значения |
|---|---|
| `theme` | `light`, `dark`, `auto` |
| `accent`, `bg`, `text`, `border`, `link` | цвет hex без `#`: `5045e5` или `fff` |
| `radius` | 0–24 |
| `size` | 12–20 |
| `pad` | 0–40 |
| `font` | ключ из `style.fonts` |
| `head`, `frame` | `0` или `1` |
| `src` | метка плагина: `wp`, `joomla`, `moodle`, `modx` (латиница и цифры, 2–12 символов) |

## 4. Проверка установки

```
POST {base}calc/widget_check.php
Content-Type: application/json

{"page": "https://example.ru/stranica.html", "lang": "ru"}
```

Ответ: `{"ok": true|false, "reason": "no_link", "text": "текст для человека", "href": "найденная ссылка", "url": "проверенный адрес"}`. Запрос идёт с сервера CMS (без заголовка `Origin`). С одного адреса — до 30 проверок в час, одна страница — не чаще раза в 15 секунд.

## 5. Согласие владельца сайта

Плагин не выводит ссылку, пока администратор не отметил согласие в настройках (правило каталога WordPress: без явного согласия нельзя добавлять на сайт внешние ссылки). Без согласия вместо виджета администратор видит подсказку, посетители — ничего.

## 6. Что видит посетитель

Калькулятор загружается с сервера MicroCalc во фрейме, в адресе фрейма передаётся адрес страницы. Плагины сами ничего о посетителях не собирают. Это нужно указать в описании плагина (раздел о приватности).
