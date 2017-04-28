<?php

namespace CUPPublicBusinessModule\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ExportRegistriesControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $customersService = $serviceLocator->getServiceLocator()->get('SharengoCore\Service\CustomersService');
        $businessService = $serviceLocator->getServiceLocator()->get('BusinessCore\Service\BusinessService');
        $invoicesService = $serviceLocator->getServiceLocator()->get('SharengoCore\Service\Invoices');
        $businessInvoicesService = $serviceLocator->getServiceLocator()->get('BusinessCore\Service\BusinessInvoiceService');
        $fleetService = $serviceLocator->getServiceLocator()->get('SharengoCore\Service\FleetService');
        $businessFleetService = $serviceLocator->getServiceLocator()->get('BusinessCore\Service\BusinessFleetService');
        $logger = $serviceLocator->getServiceLocator()->get('SharengoCore\Service\SimpleLoggerService');
        $emailService = $serviceLocator->getServiceLocator()->get('SharengoCore\Service\EmailService');
        $config = $serviceLocator->getServiceLocator()->get('Config');
        $exportConfig = $config['export'];
        $alertConfig = $config['alertSettings'];

        return new ExportRegistriesController(
            $customersService,
            $businessService,
            $invoicesService,
            $businessInvoicesService,
            $fleetService,
            $businessFleetService,
            $emailService,
            $logger,
            $exportConfig,
            $alertConfig
        );
    }
}
