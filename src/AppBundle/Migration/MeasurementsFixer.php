<?php


namespace AppBundle\Migration;


use AppBundle\Entity\BodyFat;
use AppBundle\Entity\BodyFatRepository;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Measurement;
use AppBundle\Entity\MeasurementRepository;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\MuscleThicknessRepository;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\TailLengthRepository;
use AppBundle\Entity\Weight;
use AppBundle\Entity\WeightRepository;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class MeasurementsFixer extends MigratorBase
{
    /** @var MeasurementRepository $measurementsRepository */
    private $measurementsRepository;

    /** @var ExteriorRepository $exteriorRepository */
    private $exteriorRepository;

    /** @var WeightRepository $weightRepository */
    private $weightRepository;

    /** @var TailLengthRepository $tailLengthRepository */
    private $tailLengthRepository;

    /** @var MuscleThicknessRepository */
    private $muscleThicknessRepository;

    /** @var BodyFatRepository */
    private $bodyFatRepository;


    /**
     * DuplicateMeasurementsFixer constructor.
     * @param ObjectManager $em
     * @param CommandUtil $cmdUtil
     * @param OutputInterface $output
     */
    public function __construct(ObjectManager $em, CommandUtil $cmdUtil, OutputInterface $output)
    {
        parent::__construct($cmdUtil, $em, $output);
        
        $this->measurementsRepository = $this->em->getRepository(Measurement::class);
        $this->exteriorRepository = $this->em->getRepository(Exterior::class);
        $this->weightRepository = $this->em->getRepository(Weight::class);
        $this->tailLengthRepository  = $this->em->getRepository(TailLength::class);
        $this->muscleThicknessRepository = $this->em->getRepository(MuscleThickness::class);
        $this->bodyFatRepository = $this->em->getRepository(BodyFat::class);
    }


    /**
     * @param bool $askConfirmationQuestion
     */
    public function deleteDuplicateMeasurements($askConfirmationQuestion = true)
    {
        $isClearDuplicates = !$askConfirmationQuestion ? true : $this->cmdUtil->generateConfirmationQuestion('Clear ALL duplicate measurements? (y/n): ');
        if ($isClearDuplicates) {
            
            $this->cmdUtil->setStartTimeAndPrintIt(6, 1, 'Deleting duplicate measurements...');

            $exteriorsDeleted = $this->exteriorRepository->deleteDuplicates();
            $message = 'Duplicates deleted, exteriors: ' . $exteriorsDeleted;
            $this->cmdUtil->advanceProgressBar(1, $message);

            $weightsDeleted = $this->weightRepository->deleteDuplicates();
            $message = $message . '| weights: ' . $weightsDeleted;
            $this->cmdUtil->advanceProgressBar(1, $message);

            $tailLengthsDeleted = $this->tailLengthRepository->deleteDuplicates();
            $message = $message . '| tailLength: ' . $tailLengthsDeleted;
            $this->cmdUtil->advanceProgressBar(1, $message);

            $muscleThicknessesDeleted = $this->muscleThicknessRepository->deleteDuplicates();
            $message = $message . '| muscle: ' . $muscleThicknessesDeleted;
            $this->cmdUtil->advanceProgressBar(1, $message);

            $bodyFatsDeleted = $this->bodyFatRepository->deleteDuplicates();
            $message = $message . '| BodyFat: ' . $bodyFatsDeleted;
            $this->cmdUtil->advanceProgressBar(1, $message);

            $totalDuplicatesDeleted = $exteriorsDeleted + $weightsDeleted + $tailLengthsDeleted + $muscleThicknessesDeleted + $bodyFatsDeleted;
            if($totalDuplicatesDeleted == 0) {
                $message =  'No duplicates deleted';
                $this->cmdUtil->setProgressBarMessage($message);
            }

            $this->cmdUtil->setEndTimeAndPrintFinalOverview();
        }
    }


}