/**
 * Project: Booking link click submit
 * Dependencies: jQuery
 * Author: Dirk Meinke
 * Homepage: http://www.die-netzwerkstatt.de/
 */
$('document').ready(function () {
	$('.tx-nwsmunicipalstatutes-booking').bind('click', function () {
		var id = $(this).data("id");
		var client = $(this).data("client");
		var provider = $(this).data("provider");
		var host = $(location).attr('hostname');
		var uri = $(location).attr('protocol') + "//" + $(location).attr('host') + "/" + $(location).attr('pathname');
		if (document.images) {
			(new Image()).src = uri + "?type=7879&tx_nwsmunicipalstatutes_pi1[id]=" + id + "&tx_nwsmunicipalstatutes_pi1[client]=" + client + "&tx_nwsmunicipalstatutes_pi1[host]=" + host + "&tx_nwsmunicipalstatutes_pi1[provider]=" + provider;
		}
	});
});

