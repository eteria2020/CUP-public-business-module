<?php

namespace CUPPublicBusinessModule\Controller;

use BusinessCore\Entity\Business;
use BusinessCore\Service\BusinessService;
use BusinessCore\Service\BusinessTripService;
use SharengoCore\Service\SimpleLoggerService as Logger;
use Zend\Mvc\Controller\AbstractActionController;

class ConsolePaymentsController extends AbstractActionController
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var BusinessService
     */
    private $businessService;
    /**
     * @var BusinessTripService
     */
    private $businessTripService;

    /**
     * ConsolePaymentsController constructor.
     * @param Logger $logger
     * @param BusinessService $businessService
     * @param BusinessTripService $businessTripService
     */
    public function __construct(
        Logger $logger,
        BusinessService $businessService,
        BusinessTripService $businessTripService
    ) {
        $this->logger = $logger;
        $this->businessService = $businessService;
        $this->businessTripService = $businessTripService;
    }

    public function makeBusinessPayTripsAction()
    {
        $this->logger->setOutputEnvironment(Logger::OUTPUT_ON);
        $this->logger->setOutputType(Logger::TYPE_CONSOLE);

        $businessCode = $this->getRequest()->getParam('businessCode');
        $business = $this->businessService->getBusinessByCode($businessCode);

        if (!$business instanceof Business) {
            $this->logger->log("Business code not found\n");
            return;
        }

        $this->logger->log("\nStarted\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");

        $businessTripsPayments = $this->businessTripService->getTripsToBePayed($business);
        $this->logger->log("Trips found: " . count($businessTripsPayments) . "\n");

        if (count($businessTripsPayments) > 0) {
            $this->businessTripService->payTrips($business, $businessTripsPayments);
        }

        $this->logger->log("Done\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");
    }
}
