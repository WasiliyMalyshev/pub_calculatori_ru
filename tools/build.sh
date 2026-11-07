#!/bin/sh
# Сборка архивов для установки: dist/<плагин>-<версия>.zip. Запуск из корня репозитория: sh tools/build.sh
set -eu
ROOT=$(cd "$(dirname "$0")/.." && pwd)
DIST="$ROOT/dist"
rm -rf "$DIST" && mkdir -p "$DIST"
ver() { sed -n "$2" "$1" | head -1; }

# WordPress: архив с папкой microcalc-widget (её имя — адрес плагина)
V=$(ver "$ROOT/wordpress/microcalc-widget/microcalc-widget.php" 's/^ \* Version: *//p')
(cd "$ROOT/wordpress" && zip -qr "$DIST/microcalc-widget-$V.zip" microcalc-widget -x '*.DS_Store')

# Joomla: файлы плагина в корне архива
V=$(ver "$ROOT/joomla/plg_content_microcalc/microcalc.xml" 's/.*<version>\(.*\)<\/version>.*/\1/p')
(cd "$ROOT/joomla/plg_content_microcalc" && zip -qr "$DIST/plg_content_microcalc-$V.zip" . -x '*.DS_Store')

# Moodle: в архиве должна быть папка с именем фильтра (microcalc), ставится в filter/
V=$(ver "$ROOT/moodle/filter_microcalc/version.php" "s/.*release *= *'\(.*\)'.*/\1/p")
TMP=$(mktemp -d)
cp -R "$ROOT/moodle/filter_microcalc" "$TMP/microcalc"
(cd "$TMP" && zip -qr "$DIST/filter_microcalc-$V.zip" microcalc -x '*.DS_Store')
rm -rf "$TMP"

# MODX: сниппет и инструкция
(cd "$ROOT/modx" && zip -qr "$DIST/modx-microcalc.zip" microcalc -x '*.DS_Store')

ls -l "$DIST"
