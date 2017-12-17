jQuery(document).ready(function($) {

	$( '.recruiter-add-row' ).click(function() {

		var $wrap     = $(this).closest('.field');
		var max_index = 0;

		$wrap.find('input.repeated-row-index').each(function() {
			if ( parseInt( $(this).val() ) > max_index ) {
				max_index = parseInt( $(this).val() );
			}
		});

		var html = $(this).data('row').replace( /%%repeated-row-index%%/g, max_index + 1 );
		$(this).before( html );

		return false;
	});

	$( '#submit-company-form' ).on('click', '.recruiter-remove-row', function() {

		if ( confirm( recruiter_company_submission.i18n_confirm_remove ) ) {
			$(this).closest( 'div.recruiter-data-row' ).remove();
		}

		return false;
	});

	$( '#submit-company-form' ).on('click', '.job-manager-remove-uploaded-file', function() {
		$(this).closest( '.job-manager-uploaded-file' ).remove();
		return false;
	});

	$('.fieldset-links .field').sortable({
		items:'.recruiter-data-row',
		cursor:'move',
		axis:'y',
		scrollSensitivity:40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65
	});

	// Confirm navigation
	var confirm_nav = false;

	if ( $('form#company_preview').size() ) {
		confirm_nav = true;
	}

	$( 'form#submit-company-form' ).on( 'change', 'input', function() {
		confirm_nav = true;
	});

	$( 'form#submit-company-form, form#company_preview' ).submit(function() {
		confirm_nav = false;
		return true;
	});

	$(window).bind('beforeunload', function(event) {
		if ( confirm_nav ) {
			return recruiter_company_submission.i18n_navigate;
		}
	});
});
