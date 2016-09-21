<?php

namespace CUPPublicBusinessModule\Service;

use BusinessCore\Entity\Employee;
use BusinessCore\Entity\Repository\EmployeeRepository;

use Doctrine\ORM\EntityManager;
use SharengoCore\Entity\Customers;

class EmployeeService
{
    const DISABLE_GROUP_LIMIT = 'group-limit';
    /**
     * @var EmployeeRepository
     */
    private $employeeRepository;

    /**
     * BusinessService constructor.
     * @param EntityManager $entityManager
     * @param EmployeeRepository $employeeRepository
     */
    public function __construct(
        EntityManager $entityManager,
        EmployeeRepository $employeeRepository
    ) {
        $this->entityManager = $entityManager;
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * @param $id
     * @return null|Employee
     */
    public function getEmployeeFromId($id)
    {
        return $this->employeeRepository->find($id);
    }

    public function disableCustomerForGroupLimitSurpassed(Customers $customer)
    {
        $customer->disableCompanyPin(self::DISABLE_GROUP_LIMIT);
        $this->entityManager->persist($customer);
        $this->entityManager->flush();
    }

    public function reenableCustomerAfterGroupLimitSurpassed(Customers $customer)
    {
        if ($this->customerWasDisabledForGroupLimit($customer)) {
            $customer->enableCompanyPin();
            $this->entityManager->persist($customer);
            $this->entityManager->flush();
            return true;
        }
        return false;
    }

    private function customerWasDisabledForGroupLimit(Customers $customer)
    {
        $pin = $customer->getPin();
        $pins = json_decode($pin, true);
        return array_key_exists('companyPinDisabled', $pins)
            && $pins['companyPinDisabled'] === true
            && array_key_exists('disabledReason', $pins)
            && $pins['disabledReason'] === self::DISABLE_GROUP_LIMIT;
    }
}
