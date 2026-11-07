<?php
// удаление плагина: настройки и кэш каталога
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'microcalc_widget' );
foreach ( array( 'ru', 'en', 'fr' ) as $microcalc_lang ) {
	delete_option( 'microcalc_catalog_stale_' . $microcalc_lang );
	delete_transient( 'microcalc_catalog_' . $microcalc_lang );
}
