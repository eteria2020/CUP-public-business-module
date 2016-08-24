<?php

namespace CUPPublicBusinessModule;

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
