<?php
//nws_municipal_statutes RealUrl 2.x
//nws_municipal_statutes
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT'] = array_merge_recursive(
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT'],
	array(
		'fileName' => array(
			'index' => array(
				'event.ics' => array(
					'keyValues' => array(
						'type' => 7878,
					),
				),
			),
		),
		'fixedPostVars' => array(
			'_DEFAULT' => array(
				array(
					'GETvar' => 'tx_nwsmunicipalstatutes_pi1[controller]',
					'valueMap' => array(
						'veranstalter' => 'Organizer',
						'kalender' => 'Calendar',
					),
					'noMatch' => 'bypass',
				),
				array(
					'GETvar' => 'tx_nwsmunicipalstatutes_pi1[action]',
					'valueMap' => array(
						'passwort' => 'password',
						'liste' => 'list',
						'ansicht' => 'show',
						'anmeldung' => 'registration',
						'eintragen' => 'add',
						'pruefen' => 'confirm',
						'speichern' => 'save',
						'abbrechen' => 'break',
						'register' => 'register',
						'monat' => 'month',
						'ajaxliste' => 'ajaxlist',
					),
					'noMatch' => 'bypass',
				),
			),
		),
		'postVarSets' => array(
			'_DEFAULT' =>
				array(
					'site' => array(
						array(
							'GETvar' => 'tx_nwsmunicipalstatutes_pi1[browse]',
						),
					),
					'tag' => array(
						array(
							'GETvar' => 'tx_nwsmunicipalstatutes_pi1[searchday][year]',
						),
						array(
							'GETvar' => 'tx_nwsmunicipalstatutes_pi1[searchday][month]',
						),
						array(
							'GETvar' => 'tx_nwsmunicipalstatutes_pi1[searchday][day]',
						),
					),
					'auswahl' => array(
						array(
							'GETvar' => 'tx_nwsmunicipalstatutes_pi1[monthYear]',
						),
					),
					'event' => array(
						array(
							'GETvar' => 'tx_nwsmunicipalstatutes_pi1[evid]',
							'userFunc' => 'EXT:nws_municipal_statutes/Classes/Api/Realurl/ReadRealurlTitle2.php:&Nwsnet\NwsMunicipalStatutes\Api\Realurl\ReadRealurlTitle2->getEventsTitle',
							'maxLength' => 50,
						),
					),
				),
		),
	)
);

//nws_municipal_statutes RealUrl 1.x
$TYPO3_CONF_VARS['EXTCONF']['realurl']['_DEFAULT'] = array_merge_recursive(
	$TYPO3_CONF_VARS['EXTCONF']['realurl']['_DEFAULT'],
	array(
		'fileName' => array(
			'index' => array(
				'ical.ics' => array(
					'keyValues' => array(
						'type' => 7878,
					),
				),
			),
		),
		'fixedPostVars' => array(
			'_DEFAULT' => array(
				array(
					'GETvar' => 'tx_nwsmunicipalstatutes_pi1[controller]',
					'valueMap' => array(
						'veranstalter' => 'Organizer',
						'termin' => 'Events',
					),
					'noMatch' => 'bypass',
				),
				array(
					'GETvar' => 'tx_nwsmunicipalstatutes_pi1[action]',
					'valueMap' => array(
						'passwort' => 'password',
						'liste' => 'list',
						'ansicht' => 'show',
						'anmeldung' => 'registration',
						'eintragen' => 'add',
						'pruefen' => 'confirm',
						'speichern' => 'save',
						'abbrechen' => 'break',
						'register' => 'register',
					),
					'noMatch' => 'bypass',
				),
			),
		),
		'postVarSets' => array(
			'_DEFAULT' =>
				array(
					'site' => array(
						array(
							'GETvar' => 'tx_nwsmunicipalstatutes_pi1[browse]',
						),
					),
					'tag' => array(
						array(
							'GETvar' => 'tx_nwsmunicipalstatutes_pi1[searchday][year]',
						),
						array(
							'GETvar' => 'tx_nwsmunicipalstatutes_pi1[searchday][month]',
						),
						array(
							'GETvar' => 'tx_nwsmunicipalstatutes_pi1[searchday][day]',
						),
					),
					'event' => array(
						array(
							'GETvar' => 'tx_nwsmunicipalstatutes_pi1[evid]',
							'userFunc' => 'EXT:nws_municipal_statutes/Classes/Api/Realurl/ReadRealurlTitle.php:&Tx_NwsMunicipalStatutes_Api_Realurl_ReadRealurlTitle->getEventsTitle',
							'table' => 'tx_nwsmunicipalstatutes_events',
							'maxLength' => 50,
							'id_field' => 'uid',
							'alias_field' => 'title',
							'useUniqueCache' => 1,
							'useUniqueCache_conf' => array(
								'strtolower' => 1,
								'spaceCharacter' => '-',
							),
						),
					),
				),
		),
	)
);