<?php
/***************************************************************
 * Extension Manager/Repository config file for ext "nws_municipal_statutes".
 *
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Municipal statutes from the TSA interface',
	'description' => 'Reads all available municipal statutes from the TSA FullRest interface.',
	'category' => 'plugin',
	'author' => 'Dirk Meinke',
	'author_email' => 'typo3@die-netzwerkstatt.de',
	'author_company' => 'die NetzWerkstatt GmbH & Co. KG',
	'shy' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'version' => '0.1.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.6-9.5.99',
		),
		'conflicts' => array(),
		'suggests' => array(),
	)
);