# Виджет MicroCalc без плагина

Код берётся на странице настройки виджета: [microcalc.io/ru/vidzhety.html](https://microcalc.io/ru/vidzhety.html) (на других языках — [widgets.html](https://microcalc.io/widgets.html)). Там выбирается калькулятор, оформление и копируется код из трёх строк:

```html
<div class="calc-widget" data-src="https://microcalc.io/widget/ru/davlenie.html"></div>
<p class="calc-widget-link"><a href="https://microcalc.io/ru/davlenie.html">Давление</a> — microcalc.io</p>
<script src="https://microcalc.io/include/js/widget.js" async></script>
```

Вставляйте код **целиком**: вторая строка — ссылка, без которой виджет не считает. Ссылку можно оформить по-своему, но нельзя удалять, закрывать `rel="nofollow"` или `<noindex>`, прятать и создавать скриптом.

После публикации проверьте страницу внизу страницы настройки («Проверить установку»).

| Конструктор | Инструкция |
|---|---|
| Любой сайт с доступом к HTML | [html.md](html.md) |
| Tilda | [tilda.md](tilda.md) |
| uCoz / uKit | [ucoz.md](ucoz.md) |
| Wix, Google Сайты и другие конструкторы с «встраиванием» | [frames.md](frames.md) — почему автоматически не работает |
