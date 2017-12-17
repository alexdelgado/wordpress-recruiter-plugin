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

	$( '#submit-employee-form' ).on('click', '.recruiter-remove-row', function() {

		if ( confirm( recruiter_employee_submission.i18n_confirm_remove ) ) {
			$(this).closest( 'div.recruiter-data-row' ).remove();
		}

		return false;
	});

	$( '#submit-employee-form' ).on('click', '.job-manager-remove-uploaded-file', function() {
		$(this).closest( '.job-manager-uploaded-file' ).remove();
		return false;
	});

	// Confirm navigation
	var confirm_nav = false;

	if ( $('form#employee_preview').size() ) {
		confirm_nav = true;
	}

	$( 'form#submit-employee-form' ).on( 'change', 'input', function() {
		confirm_nav = true;
	});

	$( 'form#submit-employee-form, form#employee_preview' ).submit(function() {
		confirm_nav = false;
		return true;
	});

	$(window).bind('beforeunload', function(event) {
		if ( confirm_nav ) {
			return recruiter_employee_submission.i18n_navigate;
		}
	});
});
