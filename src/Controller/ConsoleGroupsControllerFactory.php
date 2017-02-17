<?php

namespace CUPPublicBusinessModule\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ConsoleGroupsControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $sharedLocator = $serviceLocator->getServiceLocator();
        $logger = $sharedLocator->get('SharengoCore\Service\SimpleLoggerService');
        $businessService = $sharedLocator->get('BusinessCore\Service\BusinessService');
        $businessTripService = $sharedLocator->get('BusinessCore\Service\BusinessTripService');
        $customerService = $sharedLocator->get('SharengoCore\Service\CustomersService');
        $employeeService = $sharedLocator->get('CUPPublicBusinessModule\Service\EmployeeService');

        return new ConsoleGroupsController(
            $logger,
            $businessService,
            $businessTripService,
            $customerService,
            $employeeService
        );
    }
}
