/**
 * Project: Organizer
 * Dependencies: jQuery,plugin LoadingOverlay
 * Author: Dirk Meinke
 * Homepage: http://www.die-netzwerkstatt.de/
 */
$('document').ready(function () {
	$('#email').bind('change', function () {
		var email = $(this).val();
		$('#username').val(email);
	});
	$('.tx-nwsmunicipalstatutes-organizer-form').bind('submit', function () {
		$.LoadingOverlay("show");
	});
	$('#organizerModal').modal({show: false}).on('show.bs.modal', function (event) {
		var button = $(event.relatedTarget);
		var url = button.attr('href');
		var modal = $(this);
		modal.find('.modal-body').html('<object data="' + url + '" type="application/pdf" width="100%" height="700px"></object>');
	})
});

