<?php

namespace CUPPublicBusinessModule\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AccountBusinessTripsServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        // Dependencies are fetched from Service Manager
        $entityManager = $serviceLocator->get('doctrine.entitymanager.orm_default');
        $bonusRepository = $entityManager->getRepository('\SharengoCore\Entity\CustomersBonus');
        $freeFaresRepository = $entityManager->getRepository('\SharengoCore\Entity\FreeFares');
        $bonusService = $serviceLocator->get('SharengoCore\Service\BonusService');
        $freeFaresService = $serviceLocator->get('SharengoCore\Service\FreeFaresService');

        $businessTimePackageService = $serviceLocator->get('BusinessCore\Service\BusinessTimePackageService');

        return new AccountBusinessTripsService(
            $entityManager,
            $bonusRepository,
            $freeFaresRepository,
            $bonusService,
            $freeFaresService,
            $businessTimePackageService
        );
    }
}
