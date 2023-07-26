<?php

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extKey) {
        $versionAsInt = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
        if ($versionAsInt < 9999999) {
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
                'Nwsnet.'.$extKey,
                'Pi1',
                [
                    'LocalLaw' => 'list,singlelist,show,showTitle,showTitleLegislator',
                ],
                // non-cacheable actions
                [
                    'LocalLaw' => 'list,singlelist',
                ]
            );
        } else {
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
        }

        if (TYPO3_MODE === 'BE') {
            if (class_exists('\TYPO3\CMS\Core\Imaging\IconRegistry')) {
                $icons = [
                    'ext-nws-municipal-statutes-wizard-icon' => 'ce_wiz.svg',
                ];
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
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:nws_municipal_statutes/Configuration/TSConfig/pageTSConfig.ts">'
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
