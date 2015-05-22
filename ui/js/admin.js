/* globals datetimepicker, wc_store_countdown_admin */
jQuery( document ).ready( function( $ ) {

	var $active = $( '#wc_store_countdown_active' ),
	    $row    = $active.closest( 'tr' ),
	    $body   = $active.closest( 'tbody' ),
	    $rows   = $body.find( 'tr' );

	if ( $active.is( ':checked' ) ) {
		$rows.show();
	} else {
		$rows.hide();
	}

	$row.show();

	$active.change( function() {
		if ( this.checked ) {
			$rows.show();
		} else {
			$rows.hide();
		}

		$row.show();
	});

	var $relative = $( '#wc_store_countdown_relative_time' ),
	    $end_desc = $( '#wc_store_countdown_end' ).next( 'span.description' );

	if ( $relative.is( ':checked' ) ) {
		$end_desc.hide();
	} else {
		$end_desc.show();
	}

	$relative.change( function() {
		if ( this.checked ) {
			$end_desc.hide();
		} else {
			$end_desc.show();
		}
	});

	var setMinTime = function( ct ) {
		var today = new Date();

		if ( today.dateFormat( 'Y-m-d' ) == ct.dateFormat( 'Y-m-d' ) ) {
			this.setOptions({
				minTime: 0
			});
		} else {
			this.setOptions({
				minTime: false
			});
		}
	}

	$( '#wc_store_countdown_end' ).datetimepicker({
		formatTime: wc_store_countdown_admin.time_format,
		format: wc_store_countdown_admin.format,
		minDate: 0,
		onSelectDate: setMinTime,
		onShow: setMinTime,
		theme: 'woocommerce'
	});

	var $bg_color = $( '#wc_store_countdown_bg_color' );

	function defaultBgColor() {
		if ( 7 !== $bg_color.val().length ) {
			$bg_color.val( '#a46497' );
			$bg_color.css({ 'background-color': 'rgb(164, 100, 151)' });
		}
	}

	defaultBgColor();

	$bg_color.on( 'focusout', function() {
		defaultBgColor();
	});

	var $text_color = $( '#wc_store_countdown_text_color' );

	function defaultTextColor() {
		if ( 7 !== $text_color.val().length ) {
			$text_color.val( '#ffffff' );
			$text_color.css({ 'background-color': 'rgb(255, 255, 255)' });
		}
	}

	defaultTextColor();

	$text_color.on( 'focusout', function() {
		defaultTextColor();
	});
});
