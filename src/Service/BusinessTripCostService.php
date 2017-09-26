<?php

namespace CUPPublicBusinessModule\Service;

use BusinessCore\Entity\BusinessFare;
use BusinessCore\Entity\BusinessTrip;
use BusinessCore\Entity\BusinessTripPayment;
use SharengoCore\Entity\Trips;

use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\EntityManager;

class BusinessTripCostService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * process a trip to compute its cost and writes it to database
     * the third boolean parameters allow the run the function without side effects
     *
     * @param Trips $trip
     * @param BusinessTrip $businessTrip
     * @param boolean $avoidPersistance
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function computeBusinessTripCost(
        Trips $trip,
        BusinessTrip $businessTrip,
        $avoidPersistance = true
    ) {
        try {
            $businessTripPayment = $this->retrieveBusinessTripPayment($trip, $businessTrip);
            $this->entityManager->getConnection()->beginTransaction();

            $this->tripCostComputed($trip);

            if ($businessTripPayment->getAmount() > 0) {
                $this->saveTripPayment($businessTripPayment);
            }

            if (!$avoidPersistance) {
                $this->entityManager->getConnection()->commit();
            } else {
                $this->entityManager->getConnection()->rollBack();
            }
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param Trips $trip
     * @param BusinessTrip $businessTrip
     * @return BusinessTripPayment
     */
    public function retrieveBusinessTripPayment(Trips $trip, BusinessTrip $businessTrip)
    {
        // retrieve the fare for the trip
        $businessFare = $businessTrip->getBusiness()->getActiveBusinessFare();

        // compute the payable minutes of the trip
        $tripMinutes = $this->cumulateMinutes($trip->getTripBills());

        // compute the minutes of parking
        $parkMinutes = $this->computeParkMinutes($trip, $tripMinutes);

        // compute the trip cost
        $cost = $this->businessTripCost($businessFare, $tripMinutes, $parkMinutes);

        return new BusinessTripPayment(
            $businessTrip->getBusiness(),
            $businessTrip,
            $cost,
            'EUR'
        );
    }

    /**
     * computes the total number of payable minutes of a trip, summing the
     * length of all the trip bills intervals
     *
     * @param PersistentCollection $tripBills
     * @return int
     */
    private function cumulateMinutes(PersistentCollection $tripBills)
    {
        $minutes = 0;

        foreach ($tripBills as $tripBill) {
            $minutes += $tripBill->getMinutes();
        }

        return $minutes;
    }

    /**
     * computes the minutes of parking of a trip
     *
     * @param Trips $trip
     * @param $tripMinutes
     * @return int
     */
    private function computeParkMinutes(Trips $trip, $tripMinutes)
    {
        // 29sec -> 0min, 30sec -> 1 min
        $tripParkMinutes = ceil(($trip->getParkSeconds() - 29) / 60);
        // we don't want to have more parking minutes than the payable length
        // of a trip
        return min($tripMinutes, $tripParkMinutes);
    }

    private function tripCostComputed(Trips $trip)
    {
        $trip->setCostComputed(true);

        $this->entityManager->persist($trip);
        $this->entityManager->flush();
    }

    /**
     * persists the newly created tripPayment record
     *
     * @param BusinessTripPayment $tripPayment
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    private function saveTripPayment(BusinessTripPayment $tripPayment)
    {
        $this->entityManager->persist($tripPayment);
        $this->entityManager->flush();
    }

    /**
     * 
     * @param BusinessFare $businessFare
     * @param type $minutes
     * @return type
     */
    private function motionMinutesToEuro(BusinessFare $businessFare, $minutes)
    {
        $previousStep = INF;

        $fare = $businessFare->getBaseFare();
        foreach ($fare->getCostSteps() as $step => $stepCost) {
            if ($minutes > $step) {
                return min($previousStep, $stepCost + $this->motionMinutesToEuro($businessFare, $minutes - $step));
            }

            $previousStep = $stepCost;
        }

        $motionFare = $fare->getMotionCostPerMinute() * (100 - $businessFare->getMotionDiscount()) / 100;
        return min($previousStep, $motionFare * $minutes);
    }

    /**
     * computes the cost of a trip considering the minutes of parking
     *
     * @param BusinessFare $businessFare
     * @param int $tripMinutes includes the parking minutes
     * @param int $parkMinutes
     * @return mixed
     */
    private function tripCost(BusinessFare $businessFare, $tripMinutes, $parkMinutes)
    {
       $parkFare = $businessFare->getBaseFareParkCostPerMinute() * (100 - $businessFare->getParkDiscount()) / 100;

       return min(
            $this->motionMinutesToEuro($businessFare, $tripMinutes),
            $this->motionMinutesToEuro($businessFare, $tripMinutes - $parkMinutes) + $parkMinutes * $parkFare
        );

    }

    /**
     * computes the cost of a trip considering the percentage of discount for a
     * given user
     *
     * @param BusinessFare $businessFare
     * @param int $tripMinutes includes the parking minutes
     * @param int $parkMinutes
     * @return float
     */
    public function businessTripCost(BusinessFare $businessFare, $tripMinutes, $parkMinutes)
    {
        return round($this->tripCost($businessFare, $tripMinutes, $parkMinutes));
    }
}
