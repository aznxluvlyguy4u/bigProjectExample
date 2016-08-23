<?php

namespace AppBundle\Command;

use AppBundle\Util\CommandUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoParseRvoCommand extends ContainerAwareCommand
{
    const TITLE = 'Fix machtigingenRVO.csv file';
    const INPUT_PATH = '/home/data/JVT/projects/NSFO/testing/machtigingenRVO.csv';
    const OUTPUT_PATH = '/home/data/JVT/projects/NSFO/testing/fixed_machtigingenRVO.csv';

    protected function configure()
    {
        $this
            ->setName('nsfo:parse:rvo')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $fileContents = file_get_contents(self::INPUT_PATH);

        $data = explode(PHP_EOL, $fileContents);
        $fileOutput = new ArrayCollection();
        foreach ($data as $item) {
            //Fix headers
            if($fileOutput->count() == 0) {
                $header = 'relationNumberKeeper;name;streetName;addressNumber;addressNumberSuffix;postalCode;city;Soort machtiging;Datum ingang;Datum einde;Code overeenkomst;Omschrijving overeenkomst';
                $fileOutput->add($header);
            } else {

                if (strpos($item, ';"') !== FALSE) {  //Top part of AddressField
                    $parts = explode(' ',$item);
                    $addressNumberWithSuffix = end($parts);
                    $addressNumberWithSuffixSeparated = $this->formatAddressNumber($addressNumberWithSuffix);
                    $item = str_replace(' '.$addressNumberWithSuffix, '; '.$addressNumberWithSuffixSeparated ,$item);

                    $item = str_replace(';"', ';' ,$item); //remove "
                    $item = $item.';';                     //add ; to the end

                    $fileOutput->add($item);

                } else if (strpos($item, ';') == FALSE) { //Middle part of AddressField
                    $item = $item.';';                     //add ; to the end
                    $item = str_replace(';;', ';' ,$item); //workaround PHP_EOL bug

                    $item = $fileOutput->last().$item;
                    $fileOutput->removeElement($fileOutput->last());
                    $fileOutput->add($item);

                } else if (strpos($item, '";') !== FALSE) {  //Bottom part of AddressField
                    $item = str_replace('";', ';' ,$item); //remove "

                    $item = $fileOutput->last().$item;
                    $fileOutput->removeElement($fileOutput->last());
                    $fileOutput->add($item);

                } else {
                    // not part of original AddressField
                    $fileOutput->add($item);
                }

            }
        }

        foreach ($fileOutput as $row) {
            file_put_contents(self::OUTPUT_PATH, $row."\n", FILE_APPEND);
        }

        $output->writeln([
            '=== PROCESS FINISHED ===',
            '',
            '']);
    }

    public function formatAddressNumber($addressNumberWithSuffix)
    {
        list($numeric,$alpha) = sscanf($addressNumberWithSuffix, "%[0-9]%d");//"%[A-Z]%d");
        $result = str_replace($numeric, $numeric.';', $addressNumberWithSuffix);

        return $result;
    }

}
