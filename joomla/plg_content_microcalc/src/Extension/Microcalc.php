<?php
/**
 * Виджет MicroCalc в материалах Joomla: {microcalc id=318 lang=ru theme=dark accent=ff8800 width=600}.
 * HTML собирается на сервере (spec/embed.md, раздел 2): ссылку должен видеть поисковый робот.
 *
 * @package     MicroCalc
 * @license     GNU General Public License version 2 or later
 */

namespace MicroCalc\Plugin\Content\Microcalc\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;

final class Microcalc extends CMSPlugin implements SubscriberInterface
{
	/** Адрес сервиса по умолчанию: задаётся только здесь, меняется в настройках плагина. */
	public const BASE = 'https://microcalc.io/';

	private const CATALOG_VERSION = 1;
	private const TTL             = 43200;
	private const COLORS          = ['accent', 'bg', 'text', 'border', 'link'];
	private const RANGES          = ['radius' => [0, 24, 6], 'size' => [12, 20, 16], 'pad' => [0, 40, 12]];
	private const FONTS           = ['system', 'inter', 'arial', 'verdana', 'tahoma', 'georgia', 'times', 'mono'];

	protected $autoloadLanguage = true;

	/** @var array<string, array|null> каталоги по языкам за время запроса */
	private array $catalogs = [];

	private bool $scriptPrinted = false;

	public static function getSubscribedEvents(): array
	{
		return ['onContentPrepare' => 'onContentPrepare'];
	}

	public function onContentPrepare(EventInterface $event): void
	{
		// Joomla 5: именованные аргументы (context, subject), Joomla 4: по порядку
		$args    = $event->getArguments();
		$context = $args['context'] ?? $args[0] ?? '';
		$item    = $args['subject'] ?? $args[1] ?? null;

		if ($context === 'com_finder.indexer' || !\is_object($item) || !isset($item->text) || stripos($item->text, '{microcalc') === false) {
			return;
		}

		// редактор оборачивает строку в <p>: div внутри p недопустим, заменяем абзац целиком
		$item->text = preg_replace_callback(
			'~(?:<p[^>]*>\s*)?\{microcalc\s+([^}]*)\}(?:\s*</p>)?~i',
			fn ($m) => $this->render($this->parseAttributes($m[1])),
			$item->text
		);
	}

	/** Атрибуты вида key=value или key="value". */
	private function parseAttributes(string $raw): array
	{
		$attrs = [];
		preg_match_all('~(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\']+))~u', html_entity_decode($raw), $m, PREG_SET_ORDER);

		foreach ($m as $pair) {
			// незадействованные группы бывают пустыми или отсутствуют
			$value = (string) ($pair[2] ?? '');
			$value = $value !== '' ? $value : (string) ($pair[3] ?? '');
			$value = $value !== '' ? $value : (string) ($pair[4] ?? '');
			$attrs[strtolower($pair[1])] = trim($value);
		}

		return $attrs;
	}

	private function render(array $atts): string
	{
		if (!(int) $this->params->get('consent', 0)) {
			return $this->notice(Text::_('PLG_CONTENT_MICROCALC_NOTICE_CONSENT'));
		}

		$id   = (int) ($atts['id'] ?? 0);
		$lang = $this->lang($atts['lang'] ?? '');

		if ($id <= 0) {
			return $this->notice(Text::_('PLG_CONTENT_MICROCALC_NOTICE_ID'));
		}

		$catalog = $this->catalog($lang);
		$item    = null;

		foreach ($catalog['items'] ?? [] as $row) {
			if ((int) $row['id'] === $id) {
				$item = $row;
				break;
			}
		}

		if (!$item) {
			return $this->notice(Text::_($catalog ? 'PLG_CONTENT_MICROCALC_NOTICE_NOT_FOUND' : 'PLG_CONTENT_MICROCALC_NOTICE_CATALOG'));
		}

		$query        = $this->styleQuery($atts);
		$query['src'] = 'joomla';
		$src          = $item['widget_src'] . (strpos($item['widget_src'], '?') === false ? '?' : '&') . http_build_query($query);
		$width        = max(0, (int) ($atts['width'] ?? 0));
		$e            = fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

		$html = '<div class="calc-widget" data-src="' . $e($src) . '"' . ($width ? ' style="max-width:' . $width . 'px"' : '') . '></div>' . "\n"
			. '<p class="calc-widget-link"><a href="' . $e($item['page_url']) . '">' . $e($item['name']) . '</a> — ' . $e($catalog['site']['name']) . '</p>' . "\n";

		if (!$this->scriptPrinted) {
			$this->scriptPrinted = true;
			$html .= '<script src="' . $e($catalog['site']['script']) . '" async></script>' . "\n";
		}

		return $html;
	}

	private function base(): string
	{
		$base = trim((string) $this->params->get('base', ''));

		return rtrim(($base !== '' && strpos($base, 'https://') === 0) ? $base : self::BASE, '/') . '/';
	}

	private function lang(string $lang): string
	{
		$allowed = ['ru', 'en', 'fr'];

		if (\in_array($lang, $allowed, true)) {
			return $lang;
		}

		$setting = (string) $this->params->get('lang', 'auto');

		if (\in_array($setting, $allowed, true)) {
			return $setting;
		}

		$site = substr((string) $this->getApplication()->getLanguage()->getTag(), 0, 2);

		return \in_array($site, $allowed, true) ? $site : 'en';
	}

	/** Параметры оформления: атрибут, иначе настройка; только допустимые значения и только отличия от умолчаний. */
	private function styleQuery(array $atts): array
	{
		$q     = [];
		$theme = ($atts['theme'] ?? '') !== '' ? $atts['theme'] : (string) $this->params->get('theme', 'light');

		if (\in_array($theme, ['dark', 'auto'], true)) {
			$q['theme'] = $theme;
		}

		if (($atts['accent'] ?? '') === '') {
			$atts['accent'] = (string) $this->params->get('accent', '');
		}

		foreach (self::COLORS as $key) {
			$v = strtolower(ltrim(trim((string) ($atts[$key] ?? '')), '#'));

			if (preg_match('/^([0-9a-f]{3}|[0-9a-f]{6})$/', $v)) {
				$q[$key] = $v;
			}
		}

		if (($atts['radius'] ?? '') === '') {
			$atts['radius'] = (string) $this->params->get('radius', 6);
		}

		foreach (self::RANGES as $key => $r) {
			$v = $atts[$key] ?? '';

			if (is_numeric($v) && (int) $v >= $r[0] && (int) $v <= $r[1] && (int) $v !== $r[2]) {
				$q[$key] = (int) $v;
			}
		}

		if (\in_array($atts['font'] ?? '', self::FONTS, true) && $atts['font'] !== 'system') {
			$q['font'] = $atts['font'];
		}

		foreach (['head', 'frame'] as $key) {
			if (($atts[$key] ?? '') === '0') {
				$q[$key] = 0;
			}
		}

		return $q;
	}

	/**
	 * Каталог (spec/embed.md, раздел 1): файл в кэше Joomla на 12 часов,
	 * при недоступном сервисе — последняя удачная копия.
	 */
	private function catalog(string $lang): ?array
	{
		if (\array_key_exists($lang, $this->catalogs)) {
			return $this->catalogs[$lang];
		}

		$base = $this->base();
		$dir  = JPATH_CACHE . '/plg_content_microcalc';
		$file = $dir . '/catalog_' . $lang . '_' . md5($base) . '.json';

		if (is_file($file) && filemtime($file) > time() - self::TTL) {
			return $this->catalogs[$lang] = json_decode((string) file_get_contents($file), true) ?: null;
		}

		$fresh = null;

		try {
			$response = HttpFactory::getHttp()->get($base . 'calc/widget_catalog.php?v=' . self::CATALOG_VERSION . '&lang=' . $lang, [], 10);

			if ((int) $response->code === 200) {
				$fresh = $this->validate(json_decode((string) $response->body, true), $base);
			}
		} catch (\Throwable $e) {
			$fresh = null;
		}

		if ($fresh) {
			if (!is_dir($dir)) {
				@mkdir($dir, 0755, true);
			}

			@file_put_contents($file, json_encode($fresh, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

			return $this->catalogs[$lang] = $fresh;
		}

		// сервис недоступен: старая копия, повторная попытка через час
		if (is_file($file)) {
			@touch($file, time() - self::TTL + 3600);

			return $this->catalogs[$lang] = json_decode((string) file_get_contents($file), true) ?: null;
		}

		return $this->catalogs[$lang] = null;
	}

	/** Адреса из каталога должны вести на настроенный сервис. */
	private function validate($data, string $base): ?array
	{
		if (!\is_array($data) || (int) ($data['version'] ?? 0) !== self::CATALOG_VERSION || empty($data['items'])) {
			return null;
		}

		$starts = fn ($url) => \is_string($url) && strpos($url, $base) === 0;

		if (!$starts($data['site']['script'] ?? '')) {
			return null;
		}

		$items = [];

		foreach ($data['items'] as $item) {
			if (isset($item['id'], $item['name']) && $starts($item['page_url'] ?? '') && $starts($item['widget_src'] ?? '')) {
				$items[] = [
					'id'         => (int) $item['id'],
					'name'       => strip_tags((string) $item['name']),
					'page_url'   => (string) $item['page_url'],
					'widget_src' => (string) $item['widget_src'],
				];
			}
		}

		return [
			'site'  => ['name' => strip_tags((string) ($data['site']['name'] ?? '')), 'script' => (string) $data['site']['script']],
			'items' => $items,
		];
	}

	/** Подсказка только для тех, кто может править материалы; посетители ничего не видят. */
	private function notice(string $text): string
	{
		$user = $this->getApplication()->getIdentity();

		if (!$user || !$user->authorise('core.edit', 'com_content')) {
			return '';
		}

		return '<p style="border:1px dashed #c52827;padding:8px 12px;color:#c52827">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p>';
	}
}
