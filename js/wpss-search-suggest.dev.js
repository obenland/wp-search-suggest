jQuery( function( $ ) {
	$( '#s' ).suggest( wpss_options.ajaxurl, {
		onSelect: function() {
			$.post( wpss_options.url, {
				action: 'wpss-post-url',
				_wpnonce: wpss_options.nonce,
				title: $( '.ac_over' ).text()
			}, function( data ) {
				if ( data ) {
					window.location = data;
				} else {
					$( '#searchform' ).submit();
				}
			}).fail( function() {
				$( '#searchform' ).submit();
			});
		}
	});
});