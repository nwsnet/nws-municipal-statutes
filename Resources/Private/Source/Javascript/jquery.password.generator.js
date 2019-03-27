/**
 * Project: Password generator
 * Description: Generates random passwords with letters and numbers and special characters
 * Dependencies: jQuery
 * Author: Dirk Meinke
 * Homepage: http://www.die-netzwerkstatt.de/
 */
$('document').ready(function () {
	// Generate a password string
	function randString(id) {
		var dataSet = $(id).attr('data-character-set').split(',');
		var possible = '';
		if ($.inArray('a-z', dataSet) >= 0) {
			possible += 'abcdefghijklmnopqrstuvwxyz';
		}
		if ($.inArray('A-Z', dataSet) >= 0) {
			possible += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		}
		if ($.inArray('0-9', dataSet) >= 0) {
			possible += '0123456789';
		}
		if ($.inArray('#', dataSet) >= 0) {
			possible += '!%&*$#|';
		}
		var text = '';
		for (var i = 0; i < $(id).attr('data-size'); i++) {
			text += possible.charAt(Math.floor(Math.random() * possible.length));
		}
		return text;
	}

	// Create a new password on page load
	$('input[data-rel="gp"]').each(function () {
		var password = $.trim($(this).val());
		if (!password) {
			var fieldConfirm = $('input[data-rel="gpc"]');
			$passString = randString($(this));
			$(this).val($passString);
			fieldConfirm.val($passString);
		}
	});

	// Create a new password
	$(".getNewPass").click(function () {
		var field = $(this).closest('div').find('input[data-rel="gp"]');
		var fieldConfirm = $('input[data-rel="gpc"]');
		$passString = randString(field);
		field.val($passString);
		fieldConfirm.val($passString);
	});

});



