/**
 * Project: Map display and data delivery
 * Description: Loading a leaflet with POI and clickable content
 * Dependencies: jQuery,leaflet
 * Author: Alexander Konradi
 * Homepage: http://www.die-netzwerkstatt.de/
 */
(function (regional_events, $) {
	var IS_IE_OR_EDGE = (function (ua) {
		return ua.indexOf('MSIE ') !== -1 || ua.indexOf('Trident/') !== -1 || ua.indexOf('Edge/') !== -1
	}(window.navigator.userAgent));

	// scripts for random images plugin
	regional_events.shuffle = function (a) {
		var j, x, i;
		for (i = a.length; i; i--) {
			j = Math.floor(Math.random() * i);
			x = a[i - 1];
			a[i - 1] = a[j];
			a[j] = x;
		}
		return a;
	};

	regional_events.renderImages = function (events, imagesWidth, imagesHeight, $targetDiv) {
		$targetDiv.empty();
		var imageRatio = imagesWidth / imagesHeight;
		var numImages = Math.ceil($targetDiv.width() / imagesWidth);
		var width = $targetDiv.width() / numImages;
		for (var i = 0; i < numImages && events[i] !== undefined; i++) {
			var hotel = events[i];
			$('<img/>').attr({
				src: hotel.image,
				width: width,
				height: width / imageRatio,
				alt: hotel.name,
				title: hotel.name
			}).appendTo(
				$('<a/>').attr({
					href: hotel.link,
					target: '_blank',
					title: hotel.name + ', ' + hotel.postalCode + ' ' + hotel.city
				}).appendTo($targetDiv)
			);
		}
	};

	// scripts for map
	regional_events.map = function (events, mapElement) {
		var zoom = $(mapElement).data("zoom");

		var houseIcon = L.icon({
			iconUrl: 'typo3conf/ext/nws_municipal_statutes/Resources/Public/Icons/icon-map-pin.svg',
			iconSize: [26, 24],
			iconAnchor: [13, 12],
			popupAnchor: [0, -12]
		});
		var LatLng = new L.LatLng(events[0].geoLat, events[0].geoLng);
		// initialize the map with the markers
		var map = L.map(mapElement, {
			scrollWheelZoom: false
		}).setView(LatLng, zoom);

		for (var i = 0; i < events.length; i++) {
			var poi = events[i];
			var marker = L.marker(new L.LatLng(poi.geoLat, poi.geoLng), {
				//icon: houseIcon,
				title: poi.locationName
			}).addTo(map);


			if (poi.title) {
				var popupContent = '<div class="media">';
				if (poi.image !== undefined) {
					popupContent += '<div class="media-left"><a href="' + poi.link + '"><img width="100" height="100" src="' + poi.image + '" class="media-object"/></a></div>';
				}
				popupContent += '<div class="media-body">';
				popupContent += '<h5 class="media-heading"><a href="' + poi.link + '">' + poi.title + '</a></h5>';
				if (poi.descriptionShort !== undefined) {
					popupContent += poi.descriptionShort;
				} else if (poi.descriptionLong !== undefined){
					popupContent += poi.descriptionLong;
				}
				popupContent += '</div>';
				popupContent += '</div>';
				marker.bindPopup(popupContent);
			}
		}

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
		}).addTo(map);
	};
}(window.regional_events = window.regional_events || {}, jQuery));
