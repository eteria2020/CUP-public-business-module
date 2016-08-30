<?php

namespace CUPPublicBusinessModule\Controller;

use BusinessCore\Entity\Business;
use BusinessCore\Service\BusinessInvoiceService;
use BusinessCore\Service\BusinessPaymentService;
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
     * @var BusinessInvoiceService
     */
    private $businessInvoiceService;
    /**
     * @var BusinessPaymentService
     */
    private $businessPaymentService;

    /**
     * ConsolePaymentsController constructor.
     * @param Logger $logger
     * @param BusinessService $businessService
     * @param BusinessTripService $businessTripService
     * @param BusinessInvoiceService $businessInvoiceService
     * @param BusinessPaymentService $businessPaymentService
     */
    public function __construct(
        Logger $logger,
        BusinessService $businessService,
        BusinessTripService $businessTripService,
        BusinessInvoiceService $businessInvoiceService,
        BusinessPaymentService $businessPaymentService
    ) {
        $this->logger = $logger;
        $this->businessService = $businessService;
        $this->businessTripService = $businessTripService;
        $this->businessInvoiceService = $businessInvoiceService;
        $this->businessPaymentService = $businessPaymentService;
    }

    public function generateBusinessInvoicesAction()
    {
        $businessCode = $this->getRequest()->getParam('businessCode');
        $business = $this->businessService->getBusinessByCode($businessCode);
        if (!$business instanceof Business) {
            $this->logger->log("Business code not found\n");
            return;
        }
        $this->logger->log("\nStarted generating invoices\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");

        $subscriptionPayments = $this->businessPaymentService->getSubscriptionPaymentToBeInvoiced($business);
        if (count($subscriptionPayments) > 0) {
            $this->logger->log('Generating invoices for ' . count($subscriptionPayments) . " subscriptions payment\n");
            $this->businessInvoiceService->createInvoiceForSubscription($business, $subscriptionPayments);
        }

        $tripPayments = $this->businessPaymentService->getTripPaymentsToBeInvoiced($business);
        if (count($tripPayments) > 0) {
            $this->logger->log('Generating invoices for ' . count($tripPayments) . " trips payment\n");
            $this->businessInvoiceService->createInvoiceForTrips($business, $tripPayments);
        }

        $extraPayements = $this->businessPaymentService->getExtraPaymentsToBeInvoiced($business);
        if (count($extraPayements) > 0) {
            $this->logger->log('Generating invoices for ' . count($extraPayements) . " extra payment\n");
            $this->businessInvoiceService->createInvoiceForExtras($business, $extraPayements);
        }

        $packagePayements = $this->businessPaymentService->getTimePackagePaymentsToBeInvoiced($business);
        if (count($packagePayements) > 0) {
            $this->logger->log('Generating invoices for ' . count($packagePayements) . " time package payment\n");
            $this->businessInvoiceService->createInvoiceForTimePackages($business, $packagePayements);
        }

        $this->logger->log("Done generating invoices\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");
    }

    public function makeBusinessPayAction()
    {
        $this->logger->setOutputEnvironment(Logger::OUTPUT_ON);
        $this->logger->setOutputType(Logger::TYPE_CONSOLE);

        $businessCode = $this->getRequest()->getParam('businessCode');
        $business = $this->businessService->getBusinessByCode($businessCode);

        if (!$business instanceof Business) {
            $this->logger->log("Business code not found\n");
            return;
        }

        if (!$business->hasActiveContract()) {
            $this->logger->log("No active contract found for this business\n");
            return;
        }

        $this->logger->log("\nStarted\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");

        $businessTripsPayments = $this->businessPaymentService->getPendingBusinessTripPayments($business);
        $this->logger->log('Trips found: ' . count($businessTripsPayments) . "\n");

        if (count($businessTripsPayments) > 0) {
            $this->businessTripService->payTrips($business, $businessTripsPayments);
        }

        $businessExtraPayments = $this->businessPaymentService->getPendingBusinessExtraPayments($business);
        $this->logger->log('Extras found: ' . count($businessExtraPayments) . "\n");

        if (count($businessExtraPayments) > 0) {
            $this->businessTripService->payExtras($business, $businessExtraPayments);
        }

        $this->logger->log("Done\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");
    }
}
