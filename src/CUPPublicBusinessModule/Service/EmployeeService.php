<?php

namespace CUPPublicBusinessModule\Service;

use BusinessCore\Entity\Employee;
use BusinessCore\Entity\Repository\EmployeeRepository;

use Doctrine\ORM\EntityManager;

class EmployeeService
{
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
}
