<?php

namespace CUPPublicBusinessModule\Controller;

use BusinessCore\Entity\BusinessEmployee;
use BusinessCore\Service\BusinessService;
use CUPPublicBusinessModule\Service\EmployeeService;
use SharengoCore\Entity\Customers;
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
     * BusinessUserAreaController constructor.
     * @param Translator $translator
     * @param EmployeeService $employeeService
     */
    public function __construct(
        Translator $translator,
        EmployeeService $employeeService
    ) {
        $this->translator = $translator;
        $this->employeeService = $employeeService;
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
}
