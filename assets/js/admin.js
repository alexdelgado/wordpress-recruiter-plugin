jQuery(document).ready(function($) {

	// Data rows
	$( "input.recruiter_add_row" ).click(function(){
		$(this).closest('table').find('tbody').append( $(this).data('row') );
		return false;
	});

	// Sorting
	$('.wc-job-manager-recruiter-repeated-rows tbody').sortable({
		items:'tr',
		cursor:'move',
		axis:'y',
		handle: 'td.sort-column',
		scrollSensitivity:40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65
	});

	// Datepicker
	$( "input#_company_expires" ).datepicker({
		dateFormat: 'yy-mm-dd',
		minDate: 0
	});

	// Settings
	$('.job-manager-settings-wrap')
		.on( 'change', '#setting-recruiter_enable_company_categories', function() {
			if ( $( this ).is(':checked') ) {
				$('#setting-recruiter_enable_default_category_multiselect, #setting-recruiter_category_filter_type').closest('tr').show();
			} else {
				$('#setting-recruiter_enable_default_category_multiselect, #setting-recruiter_category_filter_type').closest('tr').hide();
			}
		})
		.on( 'change', '#setting-recruiter_enable_registration', function() {
			if ( $( this ).is(':checked') ) {
				$('#setting-recruiter_generate_username_from_email, #setting-recruiter_registration_role').closest('tr').show();
			} else {
				$('#setting-recruiter_generate_username_from_email, #setting-recruiter_registration_role').closest('tr').hide();
			}
		});

	$('#setting-recruiter_enable_company_categories, #setting-recruiter_enable_registration').change();
});
