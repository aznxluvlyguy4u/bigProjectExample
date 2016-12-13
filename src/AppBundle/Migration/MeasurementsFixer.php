<?php


namespace AppBundle\Migration;


use AppBundle\Constant\Constant;
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
    public function removeTimeFromDateTimeInAllMeasurements($askConfirmationQuestion = true)
    {
        $isRemoveTimeFromMeasurementDates = !$askConfirmationQuestion ? true : $this->cmdUtil->generateConfirmationQuestion('Remove time (HH:mm:ss) from DateTime in all MeasurementDates? (y/n): ');
        if($isRemoveTimeFromMeasurementDates) {
            $this->measurementsRepository->removeTimeFromAllMeasurementDates();
        }
    }


    /**
     * @param bool $askConfirmationQuestion
     * @param string $mutationsFolder
     */
    public function fixMeasurements($askConfirmationQuestion = true, $mutationsFolder = null)
    {
        $isFixDuplicates = !$askConfirmationQuestion ? true : $this->cmdUtil->generateConfirmationQuestion('Fix measurements? (y/n): ');
        if ($isFixDuplicates) {

            $this->cmdUtil->setStartTimeAndPrintIt(4, 1, 'Fixing measurements...');

            $weightFixResult = $this->weightRepository->fixMeasurements();
            $message = $weightFixResult[Constant::MESSAGE_NAMESPACE];
            $this->cmdUtil->advanceProgressBar(1, $message);

            $bodyFatFixResult = $this->bodyFatRepository->fixMeasurements();
            $message = $message .'| '. $bodyFatFixResult[Constant::MESSAGE_NAMESPACE];
            $this->cmdUtil->advanceProgressBar(1, $message);

            $exteriorFixResult = $this->exteriorRepository->fixMeasurements($mutationsFolder);
            $message = $message .'| '. $exteriorFixResult[Constant::MESSAGE_NAMESPACE];
            $this->cmdUtil->advanceProgressBar(1, $message);

            $totalDuplicatesDeleted = $weightFixResult[Constant::COUNT] + $bodyFatFixResult[Constant::COUNT]
                + $exteriorFixResult[Constant::COUNT];
            if($totalDuplicatesDeleted == 0) {
                $message =  'No measurements fixed';
                $this->cmdUtil->setProgressBarMessage($message);
            }
            $this->cmdUtil->setEndTimeAndPrintFinalOverview();

            $this->printContradictingMeasurements();
        }
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


    /**
     *
     */
    public function printContradictingMeasurements()
    {
        //Final overview
        $contradictingWeightsLeft = count($this->weightRepository->getContradictingWeightsForExportFile());
        $contradictingMuscleThicknessesLeft = count($this->muscleThicknessRepository->getContradictingMuscleThicknessesForExportFile());
        $contradictingTailLengthsLeft = count($this->tailLengthRepository->getContradictingTailLengthsForExportFile());
        $contradictingBodyFatsLeft = count($this->bodyFatRepository->getContradictingBodyFatsForExportFile());
        $contradictingExteriorsLeft = count($this->exteriorRepository->getContradictingExteriorsForExportFile());
        $contradictingMeasurementsLeft = $contradictingWeightsLeft + $contradictingMuscleThicknessesLeft + $contradictingTailLengthsLeft + $contradictingExteriorsLeft;

        if($contradictingMeasurementsLeft > 0) {
            $this->output->writeln('=== Contradicting measurements left ===');
            if($contradictingWeightsLeft > 0) { $this->output->writeln('weights: '.$contradictingWeightsLeft); }
            if($contradictingMuscleThicknessesLeft > 0) { $this->output->writeln('muscleThickness: '.$contradictingMuscleThicknessesLeft); }
            if($contradictingTailLengthsLeft > 0) { $this->output->writeln('tailLengths: '.$contradictingTailLengthsLeft); }
            if($contradictingBodyFatsLeft > 0) { $this->output->writeln('bodyFats: '.$contradictingBodyFatsLeft); }
            if($contradictingExteriorsLeft > 0) { $this->output->writeln('exteriors: '.$contradictingExteriorsLeft); }

        } else {
            $this->output->writeln('No contradicting measurements left!');
        }
    }
}