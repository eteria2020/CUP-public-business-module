<?php

namespace CUPPublicBusinessModule\Controller;

use BusinessCore\Entity\Business;
use BusinessCore\Service\BusinessInvoiceService;
use BusinessCore\Service\BusinessPaymentService;
use BusinessCore\Service\BusinessService;
use BusinessCore\Service\BusinessTripService;
use DateInterval;
use SharengoCore\Service\PaymentScriptRunsService;
use SharengoCore\Service\SimpleLoggerService as Logger;
use Zend\Mvc\Controller\AbstractActionController;

class ConsoleController extends AbstractActionController
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
     * @var PaymentScriptRunsService
     */
    private $paymentScriptRunsService;

    /**
     * ConsoleController constructor.
     * @param Logger $logger
     * @param BusinessService $businessService
     * @param BusinessTripService $businessTripService
     * @param BusinessInvoiceService $businessInvoiceService
     * @param BusinessPaymentService $businessPaymentService
     * @param PaymentScriptRunsService $paymentScriptRunsService
     */
    public function __construct(
        Logger $logger,
        BusinessService $businessService,
        BusinessTripService $businessTripService,
        BusinessInvoiceService $businessInvoiceService,
        BusinessPaymentService $businessPaymentService,
        PaymentScriptRunsService $paymentScriptRunsService

    ) {
        $this->logger = $logger;
        $this->businessService = $businessService;
        $this->businessTripService = $businessTripService;
        $this->businessInvoiceService = $businessInvoiceService;
        $this->businessPaymentService = $businessPaymentService;
        $this->paymentScriptRunsService = $paymentScriptRunsService;
    }

    public function businessPayInvoiceAction()
    {
        $this->initLogger();

        $scriptId = $this->paymentScriptRunsService->scriptStarted();

        $businesses = $this->businessService->getAllBusinessesWithCreditCard();
        $count = 0;
        foreach ($businesses as $business) {
            if ($this->itsTimeForBusinessToPay($business)) {
                $this->makeBusinessPay($business);
                $count++;
            }
        }

        $this->paymentScriptRunsService->scriptEnded($scriptId);

        $this->logger->log("payment processed for " . $count . " businesses\n");
        $count = 0;
        $businesses = $this->businessService->getAllBusinesses();
        foreach ($businesses as $business) {
            if ($this->itsTimeForBusinessToBeInvoiced($business)) {
                $this->generateBusinessInvoices($business);
                $count++;
            }
        }
        $this->logger->log("invoice processed for " . $count . " businesses\n");
    }

    public function generateBusinessInvoicesAction()
    {
        $this->initLogger();
        $businessCode = $this->getRequest()->getParam('businessCode');
        $business = $this->businessService->getBusinessByCode($businessCode);
        if (!$business instanceof Business) {
            $this->logger->log("Business code not found\n");
            return;
        }
        $this->generateBusinessInvoices($business);

    }

    public function makeBusinessPayAction()
    {
        $this->initLogger();

        $businessCode = $this->getRequest()->getParam('businessCode');
        $business = $this->businessService->getBusinessByCode($businessCode);
        if (!$business instanceof Business) {
            $this->logger->log("Business code not found\n");
            return;
        }
        $this->makeBusinessPay($business);
    }

    private function makeBusinessPay(Business $business)
    {
        if (!$business->hasActiveContract()) {
            $this->logger->log("No active contract found for business " . $business->getCode() . "\n");
            return;
        }

        $this->logger->log("\nStarted payments for business " . $business->getCode() . "\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");

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

        $business->paymentExecuted();
        $this->businessService->persistBusiness($business);

        $this->logger->log("Done payments for business " . $business->getCode() . "\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");
    }

    private function initLogger()
    {
        $this->logger->setOutputEnvironment(Logger::OUTPUT_ON);
        $this->logger->setOutputType(Logger::TYPE_CONSOLE);
    }

    private function itsTimeForBusinessToPay(Business $business)
    {
        $interval = $this->getIntervalFromString($business->getPaymentFrequence());
        if (!$interval instanceof DateInterval) {
            return false;
        }

        $lastPayment = $business->getLastPaymentExecution();
        return (!$lastPayment instanceof \DateTime || $lastPayment->add($interval) < date_create());
    }

    private function itsTimeForBusinessToBeInvoiced(Business $business)
    {
        $interval = $this->getIntervalFromString($business->getInvoiceFrequence());
        if (!$interval instanceof DateInterval) {
            return false;
        }
        $lastsInvoicement = $business->getLastInvoiceExecution();
        return (!$lastsInvoicement instanceof \DateTime || $lastsInvoicement->add($interval) < date_create());
    }

    private function getIntervalFromString($frequence)
    {
        switch ($frequence) {
            case Business::FREQUENCE_WEEKLY:
                return new DateInterval('P1W');
            case Business::FREQUENCE_FORTNIGHTLY:
                return new DateInterval('P2W');
            case Business::FREQUENCE_MONTHLY:
                return new DateInterval('P1M');
        }
        return null;
    }

    private function generateBusinessInvoices(Business $business)
    {
        $this->logger->log("\nStarted generating invoices for business " . $business->getCode() . "\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");

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

        $business->invoiceExecuted();
        $this->businessService->persistBusiness($business);

        $this->logger->log("Done generating invoices for business " . $business->getCode() . "\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");
    }
}
