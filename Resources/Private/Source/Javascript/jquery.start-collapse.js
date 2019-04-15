/**
 * Project: local law
 * Dependencies: jQuery,plugin bootstrap collapse
 * Author: Dirk Meinke
 * Homepage: http://www.die-netzwerkstatt.de/
 */
$(document).ready(function () {
	if (typeof NwsMunicipalStatutesId !== "undefined") {
		$('#lg' + NwsMunicipalStatutesId + 'Cont').collapse({
			show: true
		});
	}
});