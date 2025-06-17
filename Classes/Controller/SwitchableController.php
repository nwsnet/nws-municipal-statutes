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

namespace Nwsnet\NwsMunicipalStatutes\Controller;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Http\ForwardResponse;

class SwitchableController extends AbstractController
{
    /**
     * Determines the appropriate controller and action based on FlexForm configuration and forwards the response accordingly.
     *
     * @return ForwardResponse The response object with the resolved controller and action. Defaults to the predefined controller and action if validation fails.
     */
    public function switchableAction(): ForwardResponse
    {
        $controllerActionMap = $this->mapSwitchableControllerActionsFromFlexForm();
        $controller = key($controllerActionMap) ?? self::DEFAULT_CONTROLLER;
        $action = $controllerActionMap[$controller][0] ?? self::DEFAULT_ACTION;
        if ($this->validateControllerAction($controller, $action)) {
            $response = new ForwardResponse($action);

            return $response->withControllerName($controller);
        } else {
            $response = new ForwardResponse(self::DEFAULT_ACTION);

            return $response->withControllerName(self::DEFAULT_CONTROLLER);
        }
    }

    /**
     * Validates if the specified controller and action are properly configured within the framework configuration.
     *
     * @param string $controller The name of the controller to validate (in lowercase, without the "Controller" suffix).
     * @param string $action The name of the action to validate (in lowercase, without the "Action" suffix).
     * @return bool Returns true if the controller and action are valid and exist in the framework configuration, otherwise false.
     */
    private function validateControllerAction(string $controller, string $action): bool
    {
        $controllerName = ucfirst($controller).'Controller';
        $configuration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );
        $controllerConfiguration = $configuration['controllerConfiguration'] ?? [];
        foreach ($controllerConfiguration as $controllerConfig) {
            if (true === str_ends_with($controllerConfig['className'], $controllerName)) {
                if (true === in_array($action, $controllerConfig['actions'])) {
                    return true;
                }
            }
        }

        return false;
    }
}