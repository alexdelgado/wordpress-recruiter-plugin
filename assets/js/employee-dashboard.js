jQuery(document).ready(function($) {

	$('.employee-dashboard-action-delete').click(function() {
		var answer = confirm( recruiter_employee_dashboard.i18n_confirm_delete );

		if (answer) {
			return true;
		}

		return false;
	});

});
