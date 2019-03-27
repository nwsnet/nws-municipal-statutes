/**
 * Project: Organizer Access
 * Description: Validation and login of organizers to maintain event data
 * Dependencies: Bootstrap, jQuery
 * Author: Dirk Meinke
 * Homepage: http://www.die-netzwerkstatt.de/
 */
$('document').ready(function () {

	/* validation */
	$("#login-form").validate({
		rules: {
			"tx_nwsmunicipalstatutes_pi1[PWD]": {
				required: true,
				minlength: 6
			},
			"tx_nwsmunicipalstatutes_pi1[USR]": {
				required: true,
				minlength: 3
			}
		},
		messages: {
			"tx_nwsmunicipalstatutes_pi1[PWD]": {
				required: "Bitte geben Sie Ihr Passwort ein.",
				minlength: "Bitte geben Sie ein korrektes Passwort ein."
			},
			"tx_nwsmunicipalstatutes_pi1[USR]": {
				required: "Bitte geben Sie Ihren Benutzernamen ein.",
				minlength: "Bitte geben Sie ein korrekten Benutzernamen ein."
			}
		},
		submitHandler: submitLogin
	});
	$("#login-form-register").validate({
		rules: {
			"tx_nwsmunicipalstatutes_pi1[PWD]": {
				required: true,
				minlength: 6
			},
			"tx_nwsmunicipalstatutes_pi1[USR]": {
				required: true,
				minlength: 3
			}
		},
		messages: {
			"tx_nwsmunicipalstatutes_pi1[PWD]": {
				required: "Bitte geben Sie Ihr Passwort ein.",
				minlength: "Bitte geben Sie ein korrektes Passwort ein."
			},
			"tx_nwsmunicipalstatutes_pi1[USR]": {
				required: "Bitte geben Sie Ihren Benutzernamen ein.",
				minlength: "Bitte geben Sie ein korrekten Benutzernamen ein."
			}
		},
		submitHandler: submitLoginRegister
	});

	/* login submit */
	function submitLogin() {
		var loginForm = $("#login-form");
		var action = loginForm.attr('action');
		var button = $("#btn-login");
		var data = loginForm.serialize();
		var uri = loginForm.data('uri');
		var buttonSend = button.data('send');
		var buttonLogin = button.data('login');
		$.ajax({
			type: 'POST',
			url: uri,
			data: data,
			beforeSend: function () {
				$(".error-registration").fadeOut();
				$("#btn-login").html('<span class="glyphicon glyphicon-transfer"></span> &nbsp; ' + buttonSend + ' ...');
			},
			success: function (response) {
				if (response.hasOwnProperty('error')) {
					$(".error-registration").fadeIn(1000, function () {
						$(".error-registration").html('<div class="alert alert-danger"> <span class="glyphicon glyphicon-info-sign"></span> &nbsp; ' + response.error.message + ' </div>');
						$("#btn-login").html('<i class="glyphicon glyphicon-log-in"></i>&nbsp; ' + buttonLogin);
					});
				}
				else if (response.hasOwnProperty('succsess')) {
					$("#btn-login").html('<i class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></i> &nbsp; ' + buttonLogin + ' ...');
					setTimeout(' window.location.href = "' + action + '/index.php?UIN=' + response.succsess.uin + '&kdnr=' + response.succsess.userId + '"', 2000);
				} else {
					$("#btn-login").html('<i class="glyphicon glyphicon-log-in"></i>&nbsp; ' + buttonLogin);
				}
			}
		});
		return false;
	}

	/* login submit */
	function submitLoginRegister() {
		var loginForm = $("#login-form-register");
		var action = loginForm.attr('action');
		var button = $("#btn-login-register");
		var data = loginForm.serialize();
		var uri = loginForm.data('uri');
		var buttonSend = button.data('send');
		var buttonLogin = button.data('login');
		$.ajax({
			type: 'POST',
			url: uri,
			data: data,
			beforeSend: function () {
				$(".error-register").fadeOut();
				$("#btn-login-register").html('<span class="glyphicon glyphicon-transfer"></span> &nbsp; ' + buttonSend + ' ...');
			},
			success: function (response) {
				if (response.hasOwnProperty('error')) {
					$(".error-register").fadeIn(1000, function () {
						$(".error-register").html('<div class="alert alert-danger"> <span class="glyphicon glyphicon-info-sign"></span> &nbsp; ' + response.error.message + ' </div>');
						$("#btn-login-register").html('<i class="glyphicon glyphicon-log-in"></i>&nbsp; ' + buttonLogin);
					});
				}
				else if (response.hasOwnProperty('succsess')) {
					$("#btn-login-register").html('<i class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></i> &nbsp; ' + buttonLogin + ' ...');
					setTimeout(' window.location.href = "' + action + '/index.php?UIN=' + response.succsess.uin + '&kdnr=' + response.succsess.userId + '"', 2000);
				} else {
					$("#btn-login-register").html('<i class="glyphicon glyphicon-log-in"></i>&nbsp; ' + buttonLogin);
				}
			}
		});
		return false;
	}
});