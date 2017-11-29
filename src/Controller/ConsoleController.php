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

class ConsoleController extends AbstractActionController {

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
    Logger $logger, BusinessService $businessService, BusinessTripService $businessTripService, BusinessInvoiceService $businessInvoiceService, BusinessPaymentService $businessPaymentService, PaymentScriptRunsService $paymentScriptRunsService
    ) {
        $this->logger = $logger;
        $this->businessService = $businessService;
        $this->businessTripService = $businessTripService;
        $this->businessInvoiceService = $businessInvoiceService;
        $this->businessPaymentService = $businessPaymentService;
        $this->paymentScriptRunsService = $paymentScriptRunsService;
    }

    public function businessPayInvoiceAction() {
        $this->initLogger();

        $this->logger->log(date_create()->format('Y-m-d H:i:s').";INF;businessPayInvoiceAction;start\n");

        // $scriptId = $this->paymentScriptRunsService->scriptStarted();  //TODO: temporary disabled

        $businesses = $this->businessService->getAllBusinessesWithCreditCard();
        $count = 0;
        foreach ($businesses as $business) {
            if ($this->itsTimeForBusinessToPay($business)) {
                $this->makeBusinessPay($business);
                $count++;
            }
        }

        // $this->paymentScriptRunsService->scriptEnded($scriptId);     //TODO: temporary disabled

        $this->logger->log(date_create()->format('H:i:s').";INF;businessPayInvoiceAction;end;payments;" . $count . "\n");
        $count = 0;
        $businesses = $this->businessService->getAllBusinesses();
        foreach ($businesses as $business) {
            if ($this->itsTimeForBusinessToBeInvoiced($business)) {
                $this->generateBusinessInvoices($business);
                $count++;
            }
        }
        $this->logger->log(date_create()->format('H:i:s').";INF;businessPayInvoiceAction;end;invoices;" . $count . "\n");
    }

    public function generateBusinessInvoicesAction() {
        $this->initLogger();
        $businessCode = $this->getRequest()->getParam('businessCode');
        $business = $this->businessService->getBusinessByCode($businessCode);
        if (!$business instanceof Business) {
            $this->logger->log(date_create()->format('H:i:s').";WAR;generateBusinessInvoicesAction;Business code not found\n");
            return;
        }
        $this->generateBusinessInvoices($business);
    }

    public function makeBusinessPayAction() {
        $this->initLogger();

        $businessCode = $this->getRequest()->getParam('businessCode');
        $business = $this->businessService->getBusinessByCode($businessCode);
        if (!$business instanceof Business) {
            $this->logger->log(date_create()->format('H:i:s').";WAR;makeBusinessPayAction;Business code not found\n");
            return;
        }
        $this->makeBusinessPay($business);
    }

    private function makeBusinessPay(Business $business) {
        try {

            $this->logger->log(date_create()->format('H:i:s').";INF;makeBusinessPay;".$business->getCode().";start\n");
            if (!$business->hasActiveContract()) {
                $this->logger->log(date_create()->format('H:i:s').";WAR;makeBusinessPay;".$business->getCode().";no contract\n");
                return;
            }

            $businessTripsPayments = $this->businessPaymentService->getPendingBusinessTripPayments($business);

            if (count($businessTripsPayments) > 0) {
                $this->businessTripService->payTrips($business, $businessTripsPayments);
            }

            $businessExtraPayments = $this->businessPaymentService->getPendingBusinessExtraPayments($business);

            if (count($businessExtraPayments) > 0) {
                $this->businessTripService->payExtras($business, $businessExtraPayments);
            }

            $this->logger->log(date_create()->format('H:i:s').";INF;makeBusinessPay;".$business->getCode().";trips;".count($businessTripsPayments).";extra".count($businessExtraPayments)."\n");

            $business->paymentExecuted();
            $this->businessService->persistBusiness($business);

            //$this->logger->log("Done payments for business " . $business->getCode() . "\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");
            } catch (\Exception $e) {
                $this->logger->log( date_create()->format('H:i:s').";ERR;makeBusinessPay;business->getCode;".$business->getCode()."\n");
                $this->logger->log($e->getMessage() . " " . $e->getFile() . " line " . $e->getLine() . "\n");
                $this->logger->log($e->getTraceAsString(). "\n");
        }
    }

    private function initLogger() {
        $this->logger->setOutputEnvironment(Logger::OUTPUT_ON);
        $this->logger->setOutputType(Logger::TYPE_CONSOLE);
    }

    private function itsTimeForBusinessToPay(Business $business) {
        $interval = $this->getIntervalFromString($business->getPaymentFrequence());
        if (!$interval instanceof DateInterval) {
            return false;
        }

        $lastPayment = $business->getLastPaymentExecution();
        return (!$lastPayment instanceof \DateTime || $lastPayment->add($interval) < date_create());
    }

    private function itsTimeForBusinessToBeInvoiced(Business $business) {
        $interval = $this->getIntervalFromString($business->getInvoiceFrequence());
        if (!$interval instanceof DateInterval) {
            return false;
        }
        $lastsInvoicement = $business->getLastInvoiceExecution();
        return (!$lastsInvoicement instanceof \DateTime || $lastsInvoicement->add($interval) < date_create());
    }

    private function getIntervalFromString($frequence) {
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

    private function generateBusinessInvoices(Business $business) {
        $this->logger->log(date_create()->format('H:i:s').";INF;generateBusinessInvoices;start;business->getCode;" . $business->getCode() . "\n");

        $subscriptionPayments = $this->businessPaymentService->getSubscriptionPaymentToBeInvoiced($business);
        if (count($subscriptionPayments) > 0) {
            $this->logger->log(date_create()->format('H:i:s').";INF;generateBusinessInvoices;subscriptionPayments;;" . count($subscriptionPayments) . "\n");
            $this->businessInvoiceService->createInvoiceForSubscription($business, $subscriptionPayments);
        }

        $tripPayments = $this->businessPaymentService->getTripPaymentsToBeInvoiced($business);
        if (count($tripPayments) > 0) {
            $this->logger->log(date_create()->format('H:i:s').";INF;generateBusinessInvoices;tripPayments;;" . count($tripPayments) . "\n");
            $this->businessInvoiceService->createInvoiceForTrips($business, $tripPayments);
        }

        $extraPayements = $this->businessPaymentService->getExtraPaymentsToBeInvoiced($business);
        if (count($extraPayements) > 0) {
            $this->logger->log(date_create()->format('H:i:s').";INF;generateBusinessInvoices;extraPayements;;" . count($extraPayements) . "\n");
            $this->businessInvoiceService->createInvoiceForExtras($business, $extraPayements);
        }

        $packagePayements = $this->businessPaymentService->getTimePackagePaymentsToBeInvoiced($business);
        if (count($packagePayements) > 0) {
            $this->logger->log(date_create()->format('H:i:s').";INF;generateBusinessInvoices;packagePayements;;" . count($packagePayements) . "\n");
            $this->businessInvoiceService->createInvoiceForTimePackages($business, $packagePayements);
        }

        $business->invoiceExecuted();
        $this->businessService->persistBusiness($business);

        $this->logger->log(date_create()->format('H:i:s').";INF;generateBusinessInvoices;end;$business->getCode;" . $business->getCode() . "\n");
    }

}
