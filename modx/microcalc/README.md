# MicroCalc для MODX Revolution 2.8+ / 3

Сниппет без пакета: создаётся вручную за минуту.

1. Элементы → Сниппеты → Новый сниппет, имя `microcalc`.
2. Вставьте содержимое `core/components/microcalc/elements/snippets/microcalc.snippet.php`. Можно сделать сниппет статическим и указать путь к этому файлу.
3. В шаблоне или ресурсе: ``[[microcalc? &id=`318` &agree=`1`]]``.

`&agree=`1`` — согласие показывать под виджетом открытую ссылку на microcalc.io. Без него сниппет ничего не выводит (менеджеру показывает подсказку). Его удобно задать один раз в свойствах сниппета по умолчанию.

Параметры: `&lang` (ru, en, fr; по умолчанию `cultureKey`), `&theme` (light, dark, auto), `&accent`, `&bg`, `&text`, `&border`, `&link` (цвет hex), `&radius`, `&size`, `&pad`, `&font`, `&head=`0``, `&frame=`0``, `&width`, `&base` (адрес сервиса).

Список калькуляторов хранится в кэше MODX 12 часов (`core/cache/microcalc`).
