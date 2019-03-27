/**
 * Project: Calendar Ajax calls
 * Description: The calendar provides the month selection as well as the day selection via Ajax calls
 * Dependencies: jQuery
 * Author: Dirk Meinke
 * Homepage: http://www.die-netzwerkstatt.de/
 */
$('document').ready(function () {
	$(document).on('click','.tx-nwsmunicipalstatutes-month-link', function (event) {
		var uri = event.currentTarget.getAttribute('href');
		var parent = event.currentTarget.getAttribute('data-parent');
		$.ajax({
			type: 'GET',
			url: uri,
			beforeSend: function () {
				$('.tx-nwsmunicipalstatutes-single-calendar').LoadingOverlay("show");
			},
			success: function (response) {
				$(parent).replaceWith(response);
				$('.tx-nwsmunicipalstatutes-single-calendar').LoadingOverlay("hide");
			}
		});
		return false;
	});
	$(document).on('click','.tx-nwsmunicipalstatutes-day-link', function (event) {
		var uri = event.currentTarget.getAttribute('href');
		var parent = event.currentTarget.getAttribute('data-parent');
		var target = event.currentTarget.getAttribute('data-target');
		$.ajax({
			type: 'GET',
			url: uri,
			beforeSend: function () {
				$(parent).LoadingOverlay("show");
			},
			success: function (response) {
				$('#tx-nwsmunicipalstatutes-calendar-modal .modal-body').html(response);
				$('#tx-nwsmunicipalstatutes-calendar-modal').modal('show');
				$(parent).LoadingOverlay("hide");
			}
		});
		return false;
	});
	$(document).on('click','.tx-nwsmunicipalstatutes-page-link', function (event) {
		var uri = event.currentTarget.getAttribute('href');
		var parent = event.currentTarget.getAttribute('data-parent');
		var target = event.currentTarget.getAttribute('data-target');
		$.ajax({
			type: 'GET',
			url: uri,
			beforeSend: function () {
				$(target).LoadingOverlay("show");
			},
			success: function (response) {
				$('#tx-nwsmunicipalstatutes-calendar-modal .modal-body').html(response);
				$(target).LoadingOverlay("hide");
			}
		});
		return false;
	});
});