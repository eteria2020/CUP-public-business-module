<?php

namespace CUPPublicBusinessModule\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class BusinessAssociationControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $businessService = $serviceLocator->getServiceLocator()->get('BusinessCore\Service\BusinessService');
        $associationCodeForm = $serviceLocator->getServiceLocator()->get('CUPPublicBusinessModule\Form\AssociationCodeForm');
        $employeeService = $serviceLocator->getServiceLocator()->get('CUPPublicBusinessModule\Service\EmployeeService');
        $translator = $serviceLocator->getServiceLocator()->get('Translator');
        return new BusinessAssociationController($businessService, $employeeService, $associationCodeForm, $translator);
    }
}
