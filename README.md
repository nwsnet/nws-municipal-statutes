# TYPO3 Extension ``nws-municipal-statutes``

[![Latest Stable Version](https://poser.pugx.org/nwsnet/nws-municipal-statutes/v/stable)](https://packagist.org/packages/nwsnet/nws-municipal-statutes)
[![Total Downloads](https://poser.pugx.org/nwsnet/nws-municipal-statutes/downloads)](https://packagist.org/packages/nwsnet/nws-municipal-statutes)
[![License](https://poser.pugx.org/nwsnet/nws-municipal-statutes/license)](https://packagist.org/packages/nwsnet/nws-municipal-statutes)

> At last, it is possible to publish the integration of legal norms directly on a local governmental website. The installation was easy and didn’t cause any trouble. (A user’s quote).

## 1. Features

-	Based on _Extbase_ and _Fluid_
-	Best Practices of Typo3 CMS implemented
-	Frontend template variation based on Twitter Bootstrap (v3 and v4)
-	PDF creation with _wkhtmltopdf_
-	Search for legal norms
-	Clearly represented detail 
-	Quick filter function
-	Detailed documentation

## 2. Usage

### 1) Installation

#### Installation via Composer

The recommended method of installation of this extension is the use of  [Composer][1].Just go to the Composer based Typo3-project’s root directory and type in: `composer require nwsnet/nws-municipal-statutes`. 

#### Installation as an extension from TYPO3 Extension Repository (TER)

Load the extension with your Extension Management Module and install it.

### 2) Minimalistic Setup

1) Store the API-code in your Extension Manager. Optionally you can modify the interface requests.
2) Include the static Typo Script as an extension
3) Create a plugin on any page and choose your desired view. Optionally you can apply further filter settings.

### 3) Setup site configuration for link generation
Simply add the configuration to the _"config.yaml"_ for TYPO3 9.5 and higher
>      imports:
>            - resource: 'EXT:nws_municipal_statutes/Configuration/Site/ImportSiteConfiguration.yaml'


## 3. Administration

### 3.1. Versions and support

The extension can be used for Typo3 versions 10.4 to 12.4.x and PHP Versions 7.4 – 8.4.x.

### 3.2. Release Management

The extension “nws-municipal-statutes” works with Semantic Versioning. This means:
- **Bugfix-Updates** (e.g. 1.0.0 => 1.0.1) contain only minor bug fixes or security issues without modification of the function.
- **Minor updates** (e.g. 1.0.0 => 1.1.0) contain new features or minor tasks. Changes or actions regarding the installation are not necessary.
- **Major updates** (e.g. 1.0.0 => 2.0.0) contain refactorings, features or bug fixes.

### 3.3. Contribution

- **Bugfixes:** Describe which kind of error is solved by your bugfix, and give feedback on how you could reproduce the problem. We only accept bugfixes if we can reproduce the problem.
- **New features:** Not every feature is useful for the majority of users. Besides, we want to keep the usability of the extension as simple as possible. Therefore, please deliver the described function and we will decide as a team, whether it offers any additional value.


[1]: https://getcomposer.org/