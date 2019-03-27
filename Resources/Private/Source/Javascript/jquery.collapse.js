/**
 * Project: Shortening the long text
 * Dependencies: jQuery,Collapser - Plugin v2.0
 * Author: Dirk Meinke
 * Homepage: http://www.die-netzwerkstatt.de/
 */
$(document).ready(function () {
	var longText = $('.tx-nwsmunicipalstatutes-collapse-text');
	var showText = longText.data("showtext");
	var hideText = longText.data("hidetext");
	longText.collapser({
		mode: 'lines',
		truncate: 6,
		showText: showText,
		hideText: hideText,
		controlBtn: 'btn btn-default btn-secondary',
	});
});