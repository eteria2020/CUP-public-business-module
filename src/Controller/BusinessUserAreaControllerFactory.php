<?php

namespace CUPPublicBusinessModule\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class BusinessUserAreaControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $sharedLocator = $serviceLocator->getServiceLocator();
        $config = $sharedLocator->get('Config');
        $translator = $sharedLocator->get('Translator');
        $employeeService = $sharedLocator->get('CUPPublicBusinessModule\Service\EmployeeService');
        $tripService = $sharedLocator->get('SharengoCore\Service\TripsService');
        $userService = $sharedLocator->get('zfcuser_auth_service');

        return new BusinessUserAreaController(
            $config,
            $translator,
            $employeeService,
            $tripService,
            $userService
        );
    }
}
