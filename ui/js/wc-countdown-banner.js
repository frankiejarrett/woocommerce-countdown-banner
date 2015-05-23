/* globals wc_countdown_banner */
jQuery( function( $ ) {

	$( document ).ready( function() {

		var $banner    = $( '.wc-countdown-banner' ),
		    $countdown = $( '#wccb-countdown-container' ),
		    $body      = $( 'body' );

		var original_margin = parseInt( $body.css( 'margin-top' ), 10 );

		function setBodyTopMargin() {
			var banner_height = $banner.outerHeight();

			$body.css({ 'margin-top': original_margin + banner_height });
		}

		setBodyTopMargin();

		$( window ).resize( function() {
			setBodyTopMargin();
		});

		if ( $countdown.length ) {

			var labels     = [ 'weeks', 'days', 'hours', 'minutes', 'seconds' ],
			    dateEnd    = new Date( wc_countdown_banner.end ),
			    template   = _.template( $( '#wccb-template' ).html() ),
			    currDate   = '00:00:00:00:00',
			    nextDate   = '00:00:00:00:00',
			    parser     = /([0-9]{2})/gi;

			// Parse countdown string to an object
			function strfobj( str ) {
				var parsed = str.match( parser ),
				    obj    = {};

				labels.forEach( function( label, i ) {
					obj[ label ] = parsed[ i ]
				});

				return obj;
			}

			// Return the time components that diffs
			function diff( obj1, obj2 ) {
				var diff = [];

				labels.forEach( function( key, i ) {
					if ( obj1[ key ] !== obj2[ key ] ) {
						diff.push( key );
					}
				});

				return diff;
			}

			// Build the layout
			var initData = strfobj( currDate );

			labels.forEach( function( label, i ) {
				$countdown.append(
					template({
						curr: initData[ label ],
						next: initData[ label ],
						label: label
					})
				);
			});

			// Starts the countdown
			$countdown.countdown( dateEnd, function( event ) {
				var newDate = event.strftime( '%w:%d:%H:%M:%S' ),
				    data;

				if ( newDate !== nextDate ) {
					currDate = nextDate;
					nextDate = newDate;

					// Setup the data
					data = {
						'curr': strfobj( currDate ),
						'next': strfobj( nextDate )
					};

					// Apply the new values to each node that changed
					diff( data.curr, data.next ).forEach( function( label ) {
						var selector = '.%s'.replace( /%s/, label ),
						    $node    = $countdown.find( selector );

						// Update the node
						$node.removeClass( 'wccb-flip' );
						$node.find( '.wccb-curr' ).text( data.curr[ label ] );
						$node.find( '.wccb-next' ).text( data.next[ label ] );

						// Wait for a repaint to then flip
						_.delay( function( $node ) {
							$node.addClass( 'wccb-flip' );
						}, 50, $node );
					});
				}
			});

			var hasPrev = false;

			// Remove leading empty blocks
			$countdown.children( 'div' ).each( function() {
				if ( ! hasPrev && '00' === $( this ).find( '.wccb-count.wccb-next.wccb-bottom' ).text() ) {
					hasPrev = false;
					$( this ).hide();
				} else {
					hasPrev = true;
				}
			});

		}

	});

});
