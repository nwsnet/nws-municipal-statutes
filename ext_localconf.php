<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2025 Dirk Meinke <typo3@die-netzwerkstatt.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

defined('TYPO3_MODE') || defined('TYPO3') || die('Access denied.');

call_user_func(
    function ($extKey) {
        if (defined('TYPO3_version')) {
            $versionAsInt = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
        } else {
            $version = \TYPO3\CMS\Core\Utility\VersionNumberUtility::getNumericTypo3Version();
            $versionAsInt = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger($version);
        }
        if ($versionAsInt < 12000000) {
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
                'Nwsnet.'.$extKey,
                'Pi1',
                [
                    \Nwsnet\NwsMunicipalStatutes\Controller\LocalLawController::class => 'list,singlelist,show,showTitle,showTitleLegislator',
                ],
                // non-cacheable actions
                [
                    \Nwsnet\NwsMunicipalStatutes\Controller\LocalLawController::class => 'list,singlelist',
                ]
            );
        } else{
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
                $extKey,
                'Pi1',
                [
                    \Nwsnet\NwsMunicipalStatutes\Controller\SwitchableController::class => 'switchable',
                    \Nwsnet\NwsMunicipalStatutes\Controller\LocalLawController::class => 'list,singlelist,show,showTitle,showTitleLegislator',
                ],
                // non-cacheable actions
                [
                    \Nwsnet\NwsMunicipalStatutes\Controller\SwitchableController::class => 'switchable',
                    \Nwsnet\NwsMunicipalStatutes\Controller\LocalLawController::class => 'list,singlelist',
                ]
            );
        }

        if (defined('TYPO3_MODE') && TYPO3_MODE === 'BE') {
            if (class_exists('\TYPO3\CMS\Core\Imaging\IconRegistry')) {
                $icons = [
                    'ext-nws-municipal-statutes-wizard-icon' => 'ce_wiz.svg',
                ];
                /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
                $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    \TYPO3\CMS\Core\Imaging\IconRegistry::class
                );
                foreach ($icons as $identifier => $path) {
                    $iconRegistry->registerIcon(
                        $identifier,
                        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
                        ['source' => 'EXT:nws_municipal_statutes/Resources/Public/Icons/'.$path]
                    );
                }
            }
        }

        //Add TSConfig
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:nws_municipal_statutes/Configuration/TSConfig/pageTSConfig.tsconfig">'
        );
        //Add Eid
        $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['nwsMunicipalStatutesDispatcher'] = \Nwsnet\NwsMunicipalStatutes\Eid\Dispatcher::class.'::processRequest';

        // Caching framework
        if (!is_array($GLOBALS['TYPO3_CONF_VARS'] ['SYS']['caching']['cacheConfigurations'][$extKey] ?? null)) {
            $GLOBALS['TYPO3_CONF_VARS'] ['SYS']['caching']['cacheConfigurations'][$extKey] = array();
            $GLOBALS['TYPO3_CONF_VARS'] ['SYS']['caching']['cacheConfigurations'][$extKey]['frontend'] = 'TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend';
            $GLOBALS['TYPO3_CONF_VARS'] ['SYS']['caching']['cacheConfigurations'][$extKey]['groups'] = array('pages');
            // Cache for 24 hour
            $GLOBALS['TYPO3_CONF_VARS'] ['SYS']['caching']['cacheConfigurations'][$extKey]['options'] = array('defaultLifetime' => 86400);
        }

        //For providing the title links in Sites Configuration
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['MunicipalStatusStaticActionMapper'] = \Nwsnet\NwsMunicipalStatutes\Routing\Aspect\MunicipalStatusStaticActionMapper::class;

        //Generation of the page title for TYPO3 9.5
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(trim('
            config.pageTitleProviders {
                nwsmunicipalstatutes {
                    provider = Nwsnet\NwsMunicipalStatutes\PageTitle\MunicipalPageTitleProvider
                    before = record
                    after = altPageTitle
				}
			}
		'));
    },
    'nws_municipal_statutes'
);
