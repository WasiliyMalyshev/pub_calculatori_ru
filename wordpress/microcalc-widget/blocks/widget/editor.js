/* Блок «MicroCalc» в редакторе: выбор калькулятора и оформления. На странице блок выводит сервер
   (MicroCalc_Render::html), поэтому save() пустой. Без сборки: только глобальные wp.* */
( function ( wp ) {
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var __ = function ( key ) { return ( window.microcalcWidget && window.microcalcWidget.i18n[ key ] ) || key; };
	var cfg = window.microcalcWidget || { consent: 0, settings: '', i18n: {} };
	var C = wp.components;
	var BE = wp.blockEditor;

	var cache = {};
	function loadCatalog( lang ) {
		var key = lang || 'auto';
		if ( ! cache[ key ] ) {
			cache[ key ] = wp.apiFetch( { path: '/microcalc/v1/catalog' + ( lang ? '?lang=' + encodeURIComponent( lang ) : '' ) } );
		}
		return cache[ key ];
	}

	wp.blocks.registerBlockType( 'microcalc/widget', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var state = useState( null );
			var catalog = state[ 0 ];
			var setCatalog = state[ 1 ];
			var errState = useState( '' );

			useEffect( function () {
				setCatalog( null );
				loadCatalog( a.lang ).then( setCatalog, function ( e ) { errState[ 1 ]( ( e && e.message ) || 'Error' ); } );
			}, [ a.lang ] );

			var groups = {};
			var options = [];
			if ( catalog ) {
				catalog.groups.forEach( function ( g ) { groups[ g.id ] = g.name; } );
				options = catalog.items.map( function ( it ) {
					return { value: String( it.id ), label: it.name + ( groups[ it.group ] ? ' — ' + groups[ it.group ] : '' ) };
				} );
			}
			var current = catalog && catalog.items.filter( function ( it ) { return it.id === a.calcId; } )[ 0 ];

			var inspector = el( BE.InspectorControls, {},
				el( C.PanelBody, { title: __( 'title' ), initialOpen: true },
					catalog
						? el( C.ComboboxControl, {
							label: __( 'calc' ),
							help: __( 'search' ),
							value: a.calcId ? String( a.calcId ) : '',
							options: options,
							onChange: function ( v ) { set( { calcId: v ? parseInt( v, 10 ) : 0 } ); }
						} )
						: el( 'p', {}, errState[ 0 ] || __( 'loading' ) ),
					el( C.SelectControl, {
						label: __( 'lang' ),
						value: a.lang,
						options: [ { value: '', label: __( 'auto' ) }, { value: 'ru', label: 'Русский' }, { value: 'en', label: 'English' }, { value: 'fr', label: 'Français' } ],
						onChange: function ( v ) { set( { lang: v } ); }
					} ),
					el( C.SelectControl, {
						label: __( 'theme' ),
						value: a.theme,
						options: [ { value: '', label: __( 'default' ) }, { value: 'light', label: __( 'light' ) }, { value: 'dark', label: __( 'dark' ) }, { value: 'auto', label: __( 'system' ) } ],
						onChange: function ( v ) { set( { theme: v } ); }
					} ),
					el( 'p', {}, __( 'accent' ) ),
					el( C.ColorPalette, {
						value: a.accent ? '#' + a.accent : undefined,
						onChange: function ( v ) { set( { accent: v ? String( v ).replace( '#', '' ).toLowerCase() : '' } ); }
					} ),
					el( C.RangeControl, {
						label: __( 'width' ), min: 0, max: 1200, step: 10, value: a.width,
						onChange: function ( v ) { set( { width: v || 0 } ); }
					} )
				)
			);

			// предпросмотр на странице: в редакторе адрес страницы ещё не опубликован, проверка ссылки не прошла бы
			var body = el( 'div', BE.useBlockProps( { className: 'microcalc-widget-placeholder', style: { border: '1px dashed #949494', padding: '16px', borderRadius: '6px' } } ),
				el( 'strong', {}, current ? current.name : __( 'title' ) ),
				el( 'p', { style: { margin: '6px 0 0' } }, current ? __( 'preview' ) : __( 'choose' ) ),
				cfg.consent ? null : el( 'p', { style: { color: '#d63638', margin: '6px 0 0' } }, __( 'noConsent' ), ' ', el( 'a', { href: cfg.settings, target: '_blank' }, __( 'openSettings' ) ) )
			);
			return el( wp.element.Fragment, {}, inspector, body );
		},
		save: function () { return null; }
	} );
} )( window.wp );
