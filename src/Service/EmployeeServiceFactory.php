<?php

namespace CUPPublicBusinessModule\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class EmployeeServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $entityManager = $serviceLocator->get('doctrine.entitymanager.orm_default');
        $employeeRepository = $entityManager->getRepository('BusinessCore\Entity\Employee');

        return new EmployeeService(
            $entityManager,
            $employeeRepository
        );
    }
}
