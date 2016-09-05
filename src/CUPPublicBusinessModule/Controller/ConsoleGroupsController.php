<?php

namespace CUPPublicBusinessModule\Controller;

use BusinessCore\Entity\Business;
use BusinessCore\Entity\BusinessTrip;
use BusinessCore\Entity\Group;
use BusinessCore\Service\BusinessInvoiceService;
use BusinessCore\Service\BusinessPaymentService;
use BusinessCore\Service\BusinessService;
use BusinessCore\Service\BusinessTripService;
use DateInterval;
use DateTime;
use SharengoCore\Entity\Customers;
use SharengoCore\Service\CustomersService;
use SharengoCore\Service\SimpleLoggerService as Logger;
use Zend\Mvc\Controller\AbstractActionController;

class ConsoleGroupsController extends AbstractActionController
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
     * @var CustomersService
     */
    private $customersService;

    /**
     * ConsoleController constructor.
     * @param Logger $logger
     * @param BusinessService $businessService
     * @param BusinessTripService $businessTripService
     * @param CustomersService $customersService
     */
    public function __construct(
        Logger $logger,
        BusinessService $businessService,
        BusinessTripService $businessTripService,
        CustomersService $customersService
    ) {
        $this->logger = $logger;
        $this->businessService = $businessService;
        $this->businessTripService = $businessTripService;
        $this->customersService = $customersService;
    }

    public function checkGroupsLimitsAction()
    {
        $this->initLogger();

        $businesses = $this->businessService->getAllBusinesses();
        $this->logger->log("checking group limits for " . count($businesses) . " businesses\n");
        foreach ($businesses as $business) {
            $groups = $business->getBusinessGroups();
            foreach ($groups as $group) {
                $this->checkLimits($group);
            }
        }
    }

    private function initLogger()
    {
        $this->logger->setOutputEnvironment(Logger::OUTPUT_ON);
        $this->logger->setOutputType(Logger::TYPE_CONSOLE);
    }

    private function checkLimits(Group $group)
    {
        $beginOfDay = date_create()->modify('midnight');
        $beginOfWeek = date_create()->modify('this week midnight');
        $beginOfMonth = date_create()->modify('first day of this month midnight');

        $dailyLimit = $group->getDailyMinutesLimit();
        if ($dailyLimit > 0) {
            $todayTrips = $this->businessTripService->getBusinessTripsByGroup($group, $beginOfDay);
            if ($this->isLimitSurpassed($dailyLimit, $todayTrips)) {
                $this->disableGroup($group);
                $this->logger->log("Daily limits surpassed, group " . $group->getId() . " for business " . $group->getBusiness()->getCode() . " disabled\n");
            }
        }
        $weeklyLimit = $group->getWeeklyMinutesLimit();
        if ($weeklyLimit > 0) {
            $weekTrips = $this->businessTripService->getBusinessTripsByGroup($group, $beginOfWeek);
            if ($this->isLimitSurpassed($weeklyLimit, $weekTrips)) {
                $this->disableGroup($group);
                $this->logger->log("Weekly limits surpassed, group " . $group->getId() . " for business " . $group->getBusiness()->getCode() . " disabled\n");            }
        }
        $monthlyLimit = $group->getMonthlyMinutesLimit();
        if ($monthlyLimit > 0) {
            $monthTrips = $this->businessTripService->getBusinessTripsByGroup($group, $beginOfMonth);
            if ($this->isLimitSurpassed($monthlyLimit, $monthTrips)) {
                $this->disableGroup($group);
                $this->logger->log("Monthly limits surpassed, group " . $group->getId() . " for business " . $group->getBusiness()->getCode() . " disabled\n");            }
        }
    }

    /**
     * @param $minutesLimit
     * @param BusinessTrip[] $trips
     * @return bool
     */
    private function isLimitSurpassed($minutesLimit, array $trips)
    {
        $totalMinutes = 0;
        foreach ($trips as $businessTrip) {
            $totalMinutes += $businessTrip->getTrip()->getTripLengthInMin();
        }
        return $minutesLimit < $totalMinutes;
    }

    private function disableGroup(Group $group)
    {
        $businessEmployees = $group->getBusinessEmployees();
        foreach ($businessEmployees as $businessEmployee) {
            /** @var Customers $customer */
            $customer = $this->customersService->findById($businessEmployee->getEmployee()->getId());
            $this->customersService->disableCustomerFromBusinessTrips($customer);
        }
    }
}
