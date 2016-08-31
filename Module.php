<?php

namespace CUPPublicBusinessModule;

use BusinessCore\Entity\Employee;
use CUPPublicBusinessModule\Service\EmployeeService;
use Zend\Authentication\AuthenticationService;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $serviceManager = $e->getApplication()->getServiceManager();
        $sharedEventManager  = $e->getApplication()->getEventManager()->getSharedManager();

        $this->registerEventListeners($sharedEventManager, $serviceManager);

        $application = $e->getApplication();
        $serviceManager = $application->getServiceManager();

        /** @var AuthenticationService $userService */
        $userService = $serviceManager->get('zfcuser_auth_service');
        $loggedCustomerId = $userService->getIdentity()->getId();
        /** @var EmployeeService $employeeService */
        $employeeService = $serviceManager->get('CUPPublicBusinessModule\Service\EmployeeService');
        $employee = $employeeService->getEmployeeFromId($loggedCustomerId);
        if ($employee instanceof Employee && $employee->hasActiveBusinessAssociation()) {
            $container = $serviceManager ->get('navigation');
            $businessPage = $container->findBy('route', 'area-utente/associate');
            $container->removePage($businessPage);
        }
    }

    /**
     * @param SharedEventManagerInterface $sharedEventManager
     * @param ServiceLocatorInterface $serviceManager
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     */
    private function registerEventListeners(
        SharedEventManagerInterface $sharedEventManager,
        ServiceLocatorInterface $serviceManager
    ) {
        $newEmployeeAssociated = $serviceManager->get('CUPPublicBusinessModule\Listener\NewEmployeeAssociatedListener');

        $sharedEventManager->attachAggregate($newEmployeeAssociated);
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
