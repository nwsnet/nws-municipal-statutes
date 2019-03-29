<?php
//nws_municipal_statutes RealUrl 2.x

//nws_municipal_statutes
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT'] = array_merge_recursive(
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT'],
	array(
		'fileName' => array(
			'index' => array(
				'locallaw.pdf' => array(
					'keyValues' => array(
						'type' => 6363,
					),
				),
			),
		),
		'fixedPostVars' => array(
			'_DEFAULT' => array(
				array(
					'GETvar' => 'tx_nwsmunicipalstatutes_pi1[controller]',
					'valueMap' => array(),
					'noMatch' => 'bypass',
				),
				array(
					'GETvar' => 'tx_nwsmunicipalstatutes_pi1[action]',
					'valueMap' => array(
						'liste' => 'list',
						'ansicht' => 'show',
						'einzelliste' => 'singlelist',
						'suche' => 'search',

					),
					'noMatch' => 'bypass',
				),
			),
		),
		'postVarSets' => array(
			'_DEFAULT' =>
				array(
					'gesetzgeber' => array(
						array(
							'GETvar' => 'tx_nwsmunicipalstatutes_pi1[legislator]',
							'userFunc' => 'EXT:nws_municipal_statutes/Classes/Realurl/ReadRealurlTitle.php:&Nwsnet\NwsMunicipalStatutes\Realurl\ReadRealurlTitle->getLegislatorTitle',
							'maxLength' => 50,
						),
					),
					'vorschrift' => array(
						array(
							'GETvar' => 'tx_nwsmunicipalstatutes_pi1[legalnorm]',
							'userFunc' => 'EXT:nws_municipal_statutes/Classes/Realurl/ReadRealurlTitle.php:&Nwsnet\NwsMunicipalStatutes\Realurl\ReadRealurlTitle->getLegalNormTitle',
							'maxLength' => 80,
						),
					),
				),
		),
	)
);