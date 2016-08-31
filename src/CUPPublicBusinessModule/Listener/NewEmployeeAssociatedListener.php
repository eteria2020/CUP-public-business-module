<?php

namespace CUPPublicBusinessModule\Listener;

use SharengoCore\Entity\Customers;
use SharengoCore\Service\CustomersService;
use Zend\EventManager\SharedListenerAggregateInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\EventInterface;

class NewEmployeeAssociatedListener implements SharedListenerAggregateInterface
{
    /**
     * @var array
     */
    private $listeners = [];

    /**
     * @var CustomersService
     */
    private $customersService;


    public function __construct(CustomersService $customersService)
    {
        $this->customersService = $customersService;
    }

    public function attachShared(SharedEventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(
            'BusinessService',
            'newEmployeeAssociated',
            [$this, 'newEmployeeAssociated']
        );
    }

    public function detachShared(SharedEventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $callback) {
            if ($events->detach($index, $callback)) {
                unset($this->listeners[$index]);
            }
        }
    }

    public function newEmployeeAssociated(EventInterface $e)
    {
        $params = $e->getParams();
        $employee = $params['employee'];

        /** @var Customers $customer */
        $customer = $this->customersService->findById($employee->getId());

        $primaryPin = $customer->getPrimaryPin();
        $companyPin = mt_rand(1000, 9999);
        while ($companyPin === $primaryPin) {
            $companyPin = mt_rand(1000, 9999);
        }
        $this->customersService->setPinToCustomer($customer, "company", $companyPin);
    }
}
