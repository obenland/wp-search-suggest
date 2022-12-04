jQuery(
	function( $ ) {
		$( '#s,[name="s"]' ).suggest(
			wpss_options.ajaxurl,
			{
				onSelect: function() {
					 var $form = $( this ).parents( 'form' );

					$.post(
						wpss_options.url,
						{
							action: 'wpss-post-url',
							_wpnonce: wpss_options.nonce,
							title: $( '.ac_over' ).text()
						 },
						function( data ) {
							if ( data ) {
								 window.location = data;
							} else {
									 $form.submit();
							}
						}
					).fail(
						function() {
							$form.submit();
						}
					);
				}
			}
		);
	}
);
