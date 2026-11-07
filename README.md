# MicroCalc plugins

Free step-by-step calculators, unit converters and trainers from [microcalc.io](https://microcalc.io/) on your site — in one click for popular CMS.

| Platform | Folder | How to insert |
|---|---|---|
| WordPress 6.3+ | [`wordpress/microcalc-widget`](wordpress/microcalc-widget) | MicroCalc block, `[microcalc id="318"]`, sidebar widget |
| Joomla 4 / 5 | [`joomla/plg_content_microcalc`](joomla/plg_content_microcalc) | `{microcalc id=318}` in an article |
| Moodle 4.1+ | [`moodle/filter_microcalc`](moodle/filter_microcalc) | `{microcalc id=318}` in any text |
| MODX Revolution 2.8+ / 3 | [`modx/microcalc`](modx/microcalc) | ``[[microcalc? &id=`318` &agree=`1`]]`` |
| Tilda, uCoz, any HTML site | [`instructions`](instructions) | copy the code from the widget setup page |

Ready-to-install archives are attached to [Releases](https://github.com/calculatoriru/pub_plugins/releases).

## Terms

The widget is free, with no ads and no limit on calculations. Under each widget there is a regular link to the calculator page on microcalc.io. The link must stay open to search engines (no `rel="nofollow"`, `noindex` or robots.txt block): the service checks the page and, without the link, the widget shows an error and does not calculate. Plugins show nothing until the site administrator accepts these terms in the settings.

## How it works

Plugins are thin: they load the calculator list from the service, render the embed code **on the server** (so search robots see the link) and add `widget.js`, which creates the iframe and adjusts its height. Calculation, solution, style and link check happen on the service. The format shared by all plugins: [`spec/embed.md`](spec/embed.md).

The service address (`https://microcalc.io/`) is defined in one place in each plugin and can be changed in its settings.

## Privacy

Plugins store no data about visitors. Visitors' browsers load the calculator from microcalc.io in an iframe; the address of the page with the widget is sent to the service to check the link.

## Development

- PHP 7.4+, no build step and no dependencies.
- `sh tools/build.sh` — archives in `dist/`.
- A `v*` tag runs the checks and attaches the archives to a GitHub release.

License: GPL-2.0-or-later.

---

# Плагины MicroCalc

Бесплатные калькуляторы с решением по шагам, конвертеры и тренажёры [microcalc.io](https://microcalc.io/) на вашем сайте: плагины для WordPress, Joomla, Moodle и MODX, инструкции для Tilda, uCoz и любого HTML-сайта. Таблица и ссылки — выше.

**Условия.** Виджет бесплатный, без рекламы и ограничений по числу расчётов. Под каждым виджетом — обычная ссылка на страницу калькулятора на microcalc.io, открытая для поисковых систем (без `rel="nofollow"`, `noindex` и запрета в robots.txt). Сервис проверяет страницу: без ссылки виджет показывает ошибку и не считает. Пока администратор сайта не принял условия в настройках плагина, плагин ничего не показывает.

**Как устроено.** Плагин загружает список калькуляторов с сервиса, выводит код вставки на сервере (чтобы ссылку видели поисковые роботы) и подключает `widget.js`. Расчёт, решение, оформление и проверка ссылки — на сервисе. Общий формат: [`spec/embed.md`](spec/embed.md). Адрес сервиса задан в каждом плагине в одном месте и меняется в настройках.
