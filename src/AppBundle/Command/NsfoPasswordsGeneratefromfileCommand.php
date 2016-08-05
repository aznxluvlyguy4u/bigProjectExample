<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoPasswordsGeneratefromfileCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('nsfo:passwords:generatefromfile')
            ->setDescription('...')
            ->addArgument('argument', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $argument = $input->getArgument('argument');

        if ($input->getOption('option')) {
            // ...
        }

        //ACTIVATE THE DESIRED PATH
//    $sourceFilePath = '/home/data/JVT/projects/NSFO/Migratie/FirstEmailList/nsfo_passwords_2016-08-04_NieuweEmailAdressen_GebruikersMetUbn';
//    $sourceFilePath = '/home/data/JVT/projects/NSFO/Migratie/FirstEmailList/nsfo_passwords_2016-08-04_NieuweEmailAdressen_GebruikersZonderUbn';
        $sourceFilePath = '/home/data/JVT/projects/NSFO/Migratie/FirstEmailList/nsfo_passwords_2016-08-04_NieuweEmailAdressen_GebruikersZonderUbn2';

        $changedPasswordsPath = '/home/data/JVT/projects/NSFO/Migratie/FirstEmailList/dump/nsfo_changed_passwords.txt';
        $missingClientsPath = '/home/data/JVT/projects/NSFO/Migratie/FirstEmailList/dump/nsfo_changed_passwords_missing_clients.txt';
        
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $fileContents = file_get_contents($sourceFilePath);
        $data = explode(" nsf0nline123!", $fileContents);
        $arrayOfEmails = array();
        foreach ($data as $email) {
            $email = str_replace(array(' ', "\n", "\t", "\r", "nsf0nline123!"), '', $email);

            if($email == 'weggemansa@kpnmail.nl ') {
                $email = 'weggemansa@kpnmail.nl';
            }

            if($email != ''){
                $arrayOfEmails[] = $email;
            }
        }

//    foreach ($arrayOfEmails as $email){
//      dump($email);
//    }die;

        $successCount = 0;
        $failCount = 0;

        //FIXME UNCOMMENT TO ACTIVATE
//        foreach ($arrayOfEmails as $email) {
//            $client = $em->getRepository(Client::class)->findOneBy(['emailAddress' => $email]);
//
//            if($client != null) {
//                //Create a new password
//                $passwordLength = 9;
//                $newPassword = Utils::randomString($passwordLength);
//
//                $encoder = $this->get('security.password_encoder');
//                $encodedNewPassword = $encoder->encodePassword($client, $newPassword);
//                $client->setPassword($encodedNewPassword);
//
//                $this->getDoctrine()->getEntityManager()->persist($client);
//                $this->getDoctrine()->getEntityManager()->flush();
//
//                file_put_contents($changedPasswordsPath, $email." ".$newPassword."\n", FILE_APPEND);
//                $successCount++;
//            } else {
//                file_put_contents($missingClientsPath, $email."\n", FILE_APPEND);
//                $failCount++;
//            }
//
//        }





        $output->writeln(['Passwords successfully changed' => $successCount,
            'Clients not found' => $failCount]);
        $output->writeln('Command result.');
    }

}
