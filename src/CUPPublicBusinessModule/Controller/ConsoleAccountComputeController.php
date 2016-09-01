<?php

namespace CUPPublicBusinessModule\Controller;

use BusinessCore\Entity\BusinessTrip;
use BusinessCore\Entity\Trip;
use BusinessCore\Service\BusinessTripService;
use CUPPublicBusinessModule\Service\AccountBusinessTripsService;
use CUPPublicBusinessModule\Service\BusinessTripCostService;
use SharengoCore\Entity\Trips;
use SharengoCore\Service\CustomersService;
use SharengoCore\Service\AccountTripsService;
use SharengoCore\Service\TripsService;
use SharengoCore\Service\TripCostService;
use SharengoCore\Service\SimpleLoggerService as Logger;

use Zend\Mvc\Controller\AbstractActionController;

class ConsoleAccountComputeController extends AbstractActionController
{
    /**
     * @var CustomersService
     */
    private $customerService;

    /**
     * @var AccountTripsService
     */
    private $accountTripsService;

    /**
     * @var TripsService
     */
    private $tripsService;

    /**
     * @var TripCostService
     */
    private $tripCostService;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var boolean
     */
    private $avoidPersistance;
    /**
     * @var AccountBusinessTripsService
     */
    private $accountBusinessTripsService;
    /**
     * @var BusinessTripService
     */
    private $businessTripService;
    /**
     * @var BusinessTripCostService
     */
    private $businessTripCostService;

    /**
     * @param CustomersService $customerService
     * @param BusinessTripService $businessTripService
     * @param AccountTripsService $accountTripsService
     * @param AccountBusinessTripsService $accountBusinessTripsService
     * @param TripsService $tripsService
     * @param TripCostService $tripCostService
     * @param BusinessTripCostService $businessTripCostService
     * @param Logger $logger
     */
    public function __construct(
        CustomersService $customerService,
        BusinessTripService $businessTripService,
        AccountTripsService $accountTripsService,
        AccountBusinessTripsService $accountBusinessTripsService,
        TripsService $tripsService,
        TripCostService $tripCostService,
        BusinessTripCostService $businessTripCostService,
        Logger $logger
    ) {
        $this->customerService = $customerService;
        $this->accountTripsService = $accountTripsService;
        $this->tripsService = $tripsService;
        $this->tripCostService = $tripCostService;
        $this->logger = $logger;
        $this->accountBusinessTripsService = $accountBusinessTripsService;
        $this->businessTripService = $businessTripService;
        $this->businessTripCostService = $businessTripCostService;
    }

    public function accountComputeAction()
    {
        $this->prepareLogger();
        $this->checkDryRun();

        $this->accountTrips();
        $this->computeTripsCost();
    }

    public function computeTripsCostAction()
    {
        $this->prepareLogger();
        $this->checkDryRun();

        $this->computeTripsCost();
    }

    public function computeTripCostAction()
    {
        $this->prepareLogger();
        $this->checkDryRun();

        $this->computeTripCost();

    }

    /**
     * Account trips
     *
     * The first time this action is called on a fresh database, make sure
     * trips before 05/07 are excluded (ie payable = false).
     *
     **/
    public function accountTripsAction()
    {
        $this->prepareLogger();
        $this->checkDryRun();

        $this->accountTrips();
    }

    public function accountTripAction()
    {
        $this->prepareLogger();
        $this->checkDryRun();

        $this->logger->log("\nStarted accounting trip\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");

        $tripId = $this->getRequest()->getParam('tripId');

        /** @var Trips $trip */
        $trip = $this->tripsService->getTripById($tripId);

        if ($trip->isAccountable()) {
            if ($trip->getPinType() === Trip::PIN_COMPANY) {
                $businessTrip = $this->businessTripService->getBusinessTripByTripId($trip->getId());
                if ($businessTrip instanceof BusinessTrip) {
                    $this->logger->log("Accounting business trip " . $trip->getId() . "\n");
                    $this->accountBusinessTripsService->accountBusinessTrip($trip, $businessTrip, $this->avoidPersistance);
                } else {
                    $this->logger->log("Business trip " . $trip->getId() . " skipped\n");
                }

            } else {
                $this->logger->log("Accounting private trip " . $trip->getId() . "\n");
                $this->accountTripsService->accountTrip($trip, $this->avoidPersistance);
            }
        } else {
            $this->logger->log("Trip ".$tripId." not accountable\n");
        }

        $this->logger->log("Done accounting trip\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");
    }

    private function accountTrips()
    {
        $this->logger->log("\nStarted accounting trips\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");

        $tripsToBeAccounted = $this->tripsService->getTripsToBeAccounted();

        /** @var Trips $trip */
        foreach ($tripsToBeAccounted as $trip) {
            if ($trip->isAccountable()) {
                if ($trip->getPinType() === Trip::PIN_COMPANY) {
                    $businessTrip = $this->businessTripService->getBusinessTripByTripId($trip->getId());
                    if ($businessTrip instanceof BusinessTrip) {
                        $this->logger->log("Accounting business trip " . $trip->getId() . "\n");
                        $this->accountBusinessTripsService->accountBusinessTrip($trip, $businessTrip, $this->avoidPersistance);
                    } else {
                        $this->logger->log("Business trip " . $trip->getId() . " skipped\n");
                    }

                } else {
                    $this->logger->log("Accounting private trip " . $trip->getId() . "\n");
                    $this->accountTripsService->accountTrip($trip, $this->avoidPersistance);
                }

            } else {
                if (!$this->avoidPersistance) {
                    $this->tripsService->setTripAsNotPayable($trip);
                }
            }
        }

        $this->logger->log("Done accounting trips\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");
    }

    private function prepareLogger()
    {
        $this->logger->setOutputEnvironment(Logger::OUTPUT_ON);
        $this->logger->setOutputType(Logger::TYPE_CONSOLE);
    }

    private function checkDryRun()
    {
        $request = $this->getRequest();
        $this->avoidPersistance = $request->getParam('dry-run') || $request->getParam('d');
    }

    public function computeTripsCost()
    {
        $this->logger->log("\nStarted\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");

        $tripsToBeProcessed = $this->tripsService->getTripsForCostComputation();
        $this->logger->log("Trips found: " . count($tripsToBeProcessed) . "\n");

        /** @var Trips $trip */
        foreach ($tripsToBeProcessed as $trip) {
            if ($trip->getPinType() === Trip::PIN_COMPANY) {
                $this->logger->log("Processing business trip " . $trip->getId() . "\n");
                $businessTrip = $this->businessTripService->getBusinessTripByTripId($trip->getId());
                if ($businessTrip instanceof BusinessTrip) {
                    $this->logger->log("Accounting business trip " . $trip->getId() . "\n");
                    $this->businessTripCostService->computeBusinessTripCost($trip, $businessTrip, $this->avoidPersistance);
                } else {
                    $this->logger->log("Business trip " . $trip->getId() . " skipped\n");
                }

            } else {
                $this->logger->log("Processing private trip " . $trip->getId() . "\n");
                $this->tripCostService->computeTripCost($trip, $this->avoidPersistance);
            }
        }

        $this->logger->log("Done\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");
    }

    public function computeTripCost()
    {
        $this->logger->log("\nStarted\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");

        $tripId = $this->getRequest()->getParam('tripId');
        /** @var Trips $trip */
        $trip = $this->tripsService->getTripById($tripId);
        if (!$trip->getCostComputed()) {
            if ($trip->getPinType() === Trip::PIN_COMPANY) {
                $businessTrip = $this->businessTripService->getBusinessTripByTripId($trip->getId());
                $this->logger->log("Computing cost for business trip " . $trip->getId() . "\n");
                $this->businessTripCostService->computeBusinessTripCost($trip, $businessTrip, $this->avoidPersistance);
            } else {
                $this->logger->log("Computing cost for private trip " . $trip->getId() . "\n");
                $this->tripCostService->computeTripCost($trip, $this->avoidPersistance);
            }
        } else {
            $this->logger->log("Cost already computed for trip " . $trip->getId() . "\n");
        }

        $this->logger->log("Done\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");
    }
}
