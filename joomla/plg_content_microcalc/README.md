# MicroCalc для Joomla 4 / 5

1. Расширения → Установить → загрузить `plg_content_microcalc-<версия>.zip` из [Releases](https://github.com/calculatoriru/pub_plugins/releases).
2. Расширения → Плагины → «Содержимое - MicroCalc»: включить, принять условия, при желании выбрать язык, тему и цвет кнопок.
3. В тексте материала: `{microcalc id=318}`. Номер калькулятора виден в адресе страницы настройки виджета на microcalc.io (`?calc=318`).

Параметры в фигурных скобках: `lang` (ru, en, fr), `theme` (light, dark, auto), `accent`, `bg`, `text`, `border`, `link` (цвет hex), `radius`, `size`, `pad`, `font`, `head=0`, `frame=0`, `width`. Пример: `{microcalc id=318 theme=dark accent=ff8800 width=600}`.

Список калькуляторов хранится в кэше Joomla 12 часов (`cache/plg_content_microcalc`). Требуется PHP 7.4+.
