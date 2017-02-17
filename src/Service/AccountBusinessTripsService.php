<?php

namespace CUPPublicBusinessModule\Service;

use BusinessCore\Entity\BusinessTimePackage;
use BusinessCore\Entity\BusinessTrip;
use BusinessCore\Service\BusinessTimePackageService;
use Exception;
use SharengoCore\Service\BonusService;
use SharengoCore\Entity\Repository\CustomersBonusRepository;
use SharengoCore\Entity\Repository\FreeFaresRepository;
use SharengoCore\Service\FreeFaresService;
use SharengoCore\Utils\Interval;
use SharengoCore\Entity\Trips;
use SharengoCore\Entity\TripBills;
use SharengoCore\Entity\TripBonuses;
use SharengoCore\Entity\FreeFares;
use SharengoCore\Entity\TripFreeFares;
use SharengoCore\Entity\CustomersBonus as Bonus;

use Doctrine\ORM\EntityManager;

class AccountBusinessTripsService
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var CustomersBonusRepository
     */
    private $bonusRepository;

    /**
     * @var FreeFaresRepository
     */
    private $freeFaresRepository;

    /**
     * @var BonusService
     */
    private $bonusService;

    /**
     * @var FreeFaresService
     */
    private $freeFaresService;

    /**
     * @var Trips
     */
    private $originalTrip;
    /**
     * @var BusinessTimePackageService
     */
    private $businessTimePackageService;

    public function __construct(
        EntityManager $entityManager,
        CustomersBonusRepository $bonusRepository,
        FreeFaresRepository $freeFaresRepository,
        BonusService $bonusService,
        FreeFaresService $freeFaresService,
        BusinessTimePackageService $businessTimePackageService
    ) {
        $this->entityManager = $entityManager;
        $this->bonusRepository = $bonusRepository;
        $this->freeFaresRepository = $freeFaresRepository;
        $this->bonusService = $bonusService;
        $this->freeFaresService = $freeFaresService;
        $this->businessTimePackageService = $businessTimePackageService;
    }

    /**
     * THIS IS THE ONLY ENTRY POINT TO THIS CLASS
     *
     * flags a trip as accounted after performing all the necesasry operations:
     * - writes how the trip cost needs to be accounted between free fares, boununes, and invoices
     * - updates the bounuses according to how much they were used for the trip
     *
     * @param Trips $trip
     * @param BusinessTrip $businessTrip
     * @param boolean $avoidPersistance
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function accountBusinessTrip(Trips $trip, BusinessTrip $businessTrip, $avoidPersistance = false)
    {
        $this->originalTrip = $trip;

        $this->entityManager->getConnection()->beginTransaction();

        try {
            $this->processBusinessTripAccountingDetails(clone $trip, $businessTrip);

            // flag the trip as accounted
            $trip->setIsAccounted(true);

            $this->entityManager->persist($trip);
            $this->entityManager->flush();

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
     * saves how the trip cost is split between free fares, bonuses and normal fares
     *
     * @var Trips $trip
     * @param BusinessTrip $businessTrip
     * @return array associating to bonus ids the minutes that were consumed in the trip
     * @throws \Exception
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    private function processBusinessTripAccountingDetails(Trips $trip, BusinessTrip $businessTrip)
    {
        // we search for time packages bonuses that can be used
        $this->entityManager->beginTransaction();
        try {
            $timePackages = $this->businessTimePackageService->getTimePackagesForBusinessTrip($businessTrip);
            $billableTrip = $this->applyBonuses($trip, $timePackages);
            // eventually consider billable part
            $tripBill = $this->billTrip($billableTrip);

            //persist modified things
            $this->entityManager->persist($tripBill);
            foreach ($timePackages as $businessTimePackage) {
                $this->entityManager->persist($businessTimePackage);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * Apply a list of bonuses to a trip
     *
     * @param Trips $trip
     * @param BusinessTimePackage[] $timePackages
     * @return Trips
     */
    private function applyBonuses(Trips $trip, array $timePackages)
    {
        $clonedTrip = clone $trip;

        foreach ($timePackages as $timePackage) {
            $clonedTrip = $this->applyTimePackageToTrip($clonedTrip, $timePackage);
        }

        return $clonedTrip;
    }

    /**
     * @param Trips $trip
     * @param BusinessTimePackage $businessTimePackage
     * @return \DateInterval
     */
    private function usableDateInterval(Trips $trip, BusinessTimePackage $businessTimePackage)
    {
        $start = $trip->getTimestampBeginning();
        $end = $trip->getTimestampEnd();
        $tripinterval = date_diff($start, $end);
        $packageInterval = \DateInterval::createFromDateString($businessTimePackage->getResidualMinutes() .' minutes');
        if ($tripinterval->i < $packageInterval->i) {
            return $tripinterval;
        } else {
            return $packageInterval;
        }
    }

    /**
     * Apply a single bonus to a single trip
     * Returns the modified bonus and an array of trips obtained by removing
     * the bonus periods from the trip
     *
     * @param Trips $trip
     * @param BusinessTimePackage $businessTimePackage
     * @return Trips
     */
    private function applyTimePackageToTrip(Trips $trip, BusinessTimePackage $businessTimePackage)
    {
        $interval = $this->usableDateInterval($trip, $businessTimePackage);

        if ($interval->i > 0) {
            $businessTimePackage->setResidualMinutes($businessTimePackage->getResidualMinutes() - $interval->i);
            $trip = $this->removeIntervalFromTrip($trip, $interval);
        }

        return $trip;
    }

    /**
     * removes an interval from a single trip. Returns what it remains
     *
     * @param Trips $trip
     * @param \DateInterval $interval
     * @return Trips
     */
    private function removeIntervalFromTrip(Trips $trip, \DateInterval $interval)
    {
        $trip->setTimestampBeginning($trip->getTimestampBeginning()->add($interval));

        return $trip;
    }

    /**
     * Bills a trip
     *
     * @param Trips $trip
     * @return TripBills
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    private function billTrip(Trips $trip)
    {
        $billTrip = TripBills::createFromTrip($trip);
        $billTrip->setTrip($this->originalTrip);

        return $billTrip;
    }
}
