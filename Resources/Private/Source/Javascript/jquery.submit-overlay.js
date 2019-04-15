/**
 * Project: local law
 * Dependencies: jQuery,plugin LoadingOverlay
 * Author: Dirk Meinke
 * Homepage: http://www.die-netzwerkstatt.de/
 */
$('document').ready(function () {

	$("#tx-nwsmunicipalstatutes-clearButton, #tx-nwsmunicipalstatutes-searchButton").click(function () {
		$('.tx-nwsmunicipalstatutes-content').LoadingOverlay("show");
	});

});