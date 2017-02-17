<?php

namespace CUPPublicBusinessModule\Listener;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class NewEmployeeAssociatedListenerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $customerService = $serviceLocator->get('SharengoCore\Service\CustomersService');

        return new NewEmployeeAssociatedListener($customerService);
    }
}
