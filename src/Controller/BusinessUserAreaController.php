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
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $serverInstance;

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
     * @param array $config
     * @param Translator $translator
     * @param EmployeeService $employeeService
     * @param TripsService $tripsService
     * @param AuthenticationService $authService
     */
    public function __construct(
        array $config,
        Translator $translator,
        EmployeeService $employeeService,
        TripsService $tripsService,
        AuthenticationService $authService
    ) {
        $this->config = $config;
        $this->translator = $translator;
        $this->employeeService = $employeeService;
        $this->tripsService = $tripsService;
        $this->authService = $authService;

        if(isset($this->config['serverInstance'])) {
            $this->serverInstance = $this->config['serverInstance'];
        } else {
            $this->serverInstance["id"] = "";
        }
    }

    public function pinAction()
    {
        //if there is mobile param the layout changes
        $mobile = $this->params()->fromRoute('mobile');

        if ($mobile) {
            $this->layout('layout/map');
        }

        $email = 'servizioclienti@sharengo.eu';
        $linkHowWork = 'http://site.sharengo.it/come-funziona/';

        switch ($this->serverInstance["id"]) {
            case 'nl_NL':
                $email = 'support@sharengo.nl';
                $linkHowWork = 'https://site.sharengo.nl/hoe-werkt-het/';
                break;
            case 'sk_SK':
                $email = 'zakaznickyservis@sharengo.sk';
                $linkHowWork = 'https://site.sharengo.sk/ako-funguje-sharengo/';
                break;
            case 'sl_SI':
                $email = 'support@sharengo.si';
                $linkHowWork = 'https://site.sharengo.si/ako-funguje-sharengo/';
            break;
        }


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
                'email' => $email,
                'linkHowWork' => $linkHowWork,
                'businesses' => $businesses,
                'mobile' => $mobile
            ]
        );
    }

    public function rentsAction()
    {
        //if there is mobile param the layout changes
        $mobile = $this->params()->fromRoute('mobile');
        if ($mobile) {
            $this->layout('layout/map');
        }
        $customer = $this->authService->getIdentity();
        $availableDates = $this->tripsService->getDistinctDatesForCustomerByMonth($customer);

        return new ViewModel(
            ['availableDates' => $availableDates]
        );
    }
}
