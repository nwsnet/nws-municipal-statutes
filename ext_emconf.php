<?php
/***************************************************************
 * Extension Manager/Repository config file for ext "nws_municipal_statutes".
 *
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF['nws_municipal_statutes'] = array(
	'title' => 'Municipal statutes from the TSA interface',
	'description' => 'Reads all available municipal statutes from the TSA FullRest interface.',
	'category' => 'plugin',
	'author' => 'Dirk Meinke',
	'author_email' => 'typo3@die-netzwerkstatt.de',
	'author_company' => 'die NetzWerkstatt GmbH & Co. KG',
	'state' => 'beta',
	'clearCacheOnLoad' => 1,
	'version' => '0.4.6',
	'constraints' => array(
		'depends' => array(
			'typo3' => '9.5.0-11.5.99',
		),
		'conflicts' => array(),
		'suggests' => array(),
	)
);