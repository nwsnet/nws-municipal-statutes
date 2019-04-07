mod.wizards.newContentElement.wizardItems.plugins {
	elements {
		nwsmunicipalstatutes {
			icon = ../typo3conf/ext/nws_municipal_statutes/Resources/Public/Icons/ce_wiz.gif
			iconIdentifier = ext-nws-municipal-statutes-wizard-icon
			title = LLL:EXT:nws_municipal_statutes/Resources/Private/Language/locallang.xlf:tx_nwsmunicipalstatutes_pi1.title
			description = LLL:EXT:nws_municipal_statutes/Resources/Private/Language/locallang.xlf:tx_nwsmunicipalstatutes_pi1.wiz_description
			tt_content_defValues {
				CType = list
				list_type = nwsmunicipalstatutes_pi1
			}
		}
	}
}
