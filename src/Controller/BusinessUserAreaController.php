<?php

namespace CUPPublicBusinessModule\Controller;

use BusinessCore\Entity\BusinessEmployee;
use BusinessCore\Service\BusinessService;
use CUPPublicBusinessModule\Service\EmployeeService;
use SharengoCore\Entity\Customers;
use SharengoCore\Service\TripsService;
use SharengoCore\Service\UsersService;
use Zend\Authentication\AuthenticationService;
use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\I18n\Translator;
use Zend\View\Model\ViewModel;

class BusinessUserAreaController extends AbstractActionController
{
    /**
     * @var Translator
     */
    private $translator;
    /**
     * @var EmployeeService
     */
    private $employeeService;
    /**
     * @var TripsService
     */
    private $tripsService;
    /**
     * @var AuthenticationService
     */
    private $authService;

    /**
     * BusinessUserAreaController constructor.
     * @param Translator $translator
     * @param EmployeeService $employeeService
     * @param TripsService $tripsService
     * @param AuthenticationService $authService
     */
    public function __construct(
        Translator $translator,
        EmployeeService $employeeService,
        TripsService $tripsService,
        AuthenticationService $authService
    ) {
        $this->translator = $translator;
        $this->employeeService = $employeeService;
        $this->tripsService = $tripsService;
        $this->authService = $authService;
    }

    public function pinAction()
    {
        /** @var Customers $customer */
        $customer = $this->identity();
        $employee = $this->employeeService->getEmployeeFromId($customer->getId());

        $businesses = [];
        /** @var BusinessEmployee $businessEmployee */
        foreach ($employee->getBusinessEmployee() as $businessEmployee) {
            $businesses[] = $businessEmployee->getBusiness();
        }
        return new ViewModel(
            [
                'businesses' => $businesses
            ]
        );
    }

    public function rentsAction()
    {
        $customer = $this->authService->getIdentity();
        $availableDates = $this->tripsService->getDistinctDatesForCustomerByMonth($customer);

        return new ViewModel(
            ['availableDates' => $availableDates]
        );
    }
}
