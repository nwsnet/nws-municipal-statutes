/**
 * Project: Events
 * Dependencies: jQuery,plugin LoadingOverlay
 * Author: Dirk Meinke
 * Homepage: http://www.die-netzwerkstatt.de/
 */
$('document').ready(function () {

	$("#tx-nwsmunicipalstatutes-monthYear, #tx-nwsmunicipalstatutes-province, #tx-nwsmunicipalstatutes-district, #tx-nwsmunicipalstatutes-place, #tx-nwsmunicipalstatutes-category").change(function () {
		$.LoadingOverlay("show");
		$('#tx-nwsmunicipalstatutes-button-search').click();
	});

});