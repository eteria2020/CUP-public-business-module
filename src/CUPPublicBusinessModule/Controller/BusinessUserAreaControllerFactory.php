<?php

namespace CUPPublicBusinessModule\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class BusinessUserAreaControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $translator = $serviceLocator->getServiceLocator()->get('Translator');
        $employeeService = $serviceLocator->getServiceLocator()->get('CUPPublicBusinessModule\Service\EmployeeService');
        return new BusinessUserAreaController($translator, $employeeService);
    }
}
