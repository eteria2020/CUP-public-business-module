<?php

namespace CUPPublicBusinessModule\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ConsolePaymentsControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $sharedLocator = $serviceLocator->getServiceLocator();
        $logger = $sharedLocator->get('SharengoCore\Service\SimpleLoggerService');
        $businessService = $sharedLocator->get('BusinessCore\Service\BusinessService');
        $businessTripService = $sharedLocator->get('BusinessCore\Service\BusinessTripService');
        $businessInvoiceService = $sharedLocator->get('BusinessCore\Service\BusinessInvoiceService');

        $businessPaymentService = $sharedLocator->get('BusinessCore\Service\BusinessPaymentService');


        return new ConsolePaymentsController(
            $logger,
            $businessService,
            $businessTripService,
            $businessInvoiceService,
            $businessPaymentService
        );
    }
}
