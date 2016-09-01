<?php

namespace CUPPublicBusinessModule\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ConsoleAccountComputeControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $sharedServiceManager = $serviceLocator->getServiceLocator();

        $customerService = $serviceLocator->getServiceLocator()->get('SharengoCore\Service\CustomersService');
        $accountTripsService = $sharedServiceManager->get('SharengoCore\Service\AccountTripsService');
        $tripsService = $sharedServiceManager->get('SharengoCore\Service\TripsService');
        $tripCostService = $sharedServiceManager->get('SharengoCore\Service\TripCostService');
        $logger = $sharedServiceManager->get('SharengoCore\Service\SimpleLoggerService');

        $businessTripCostService = $sharedServiceManager->get('CUPPublicBusinessModule\Service\BusinessTripCostService');
        $accountBusinessTripsService = $sharedServiceManager->get('CUPPublicBusinessModule\Service\AccountBusinessTripsService');
        $businessTripService = $sharedServiceManager->get('BusinessCore\Service\BusinessTripService');

        return new ConsoleAccountComputeController(
            $customerService,
            $businessTripService,
            $accountTripsService,
            $accountBusinessTripsService,
            $tripsService,
            $tripCostService,
            $businessTripCostService,
            $logger
        );
    }
}
