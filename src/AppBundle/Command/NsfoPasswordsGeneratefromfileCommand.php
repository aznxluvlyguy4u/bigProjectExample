<?php

namespace AppBundle\Command;

use AppBundle\Component\Utils;
use AppBundle\Entity\Client;
use AppBundle\Util\CommandUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class NsfoPasswordsGeneratefromfileCommand
 * @package AppBundle\Command
 */
class NsfoPasswordsGeneratefromfileCommand extends ContainerAwareCommand
{
    const DEFAULT_PASSWORD = 'nsf0nline123!';
    const PASSWORD_LENGTH = 9;

    protected function configure()
    {
        $this
            ->setName('nsfo:passwords:generatefromfile')
            ->setDescription('Change the passwords of clients, by a text file containing their emails')
            ->addOption('option', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle('Change client passwords from list of email adresses in a text file'));

        //Source file input
        $sourceFilePath = $cmdUtil->generateQuestion('Please enter inputfile path (containing the email addresses of the clients)',
                                                     '/tmp/nsfo_passwords.txt');

        //Output folder input
        $outputFolderPath = $cmdUtil->generateQuestion('Please enter output folder path', '/tmp/dump-clients/');
        $changedPasswordsPath = $outputFolderPath.'/nsfo_changed_passwords.txt';
        $missingClientsPath = $outputFolderPath.'/nsfo_changed_passwords_missing_clients.txt';
        
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $fileContents = file_get_contents($sourceFilePath);
        $data = explode(" ".self::DEFAULT_PASSWORD, $fileContents);
        $arrayOfEmails = array();
        foreach ($data as $email) {
            $email = str_replace(array(' ', "\n", "\t", "\r", self::DEFAULT_PASSWORD), '', $email);

            if($email != ''){
                $arrayOfEmails[] = $email;
            }
        }

        //Confirmation Check
        if(!$cmdUtil->generateConfirmationQuestion('Reset client passwords?')){
            $output->writeln('=== PROCESS ABORTED ===');
            return; //Exit
        };

        //Change passwords
        $successCount = 0;
        $failCount = 0;
        foreach ($arrayOfEmails as $email) {

            /** @var Client $client */
            $client = $em->getRepository(Client::class)->findOneBy(['emailAddress' => $email]);

            if($client != null) {
                $newPassword = Utils::randomString(self::PASSWORD_LENGTH);

                $encoder = $this->getContainer()->get('security.password_encoder');
                $encodedNewPassword = $encoder->encodePassword($client, $newPassword);
                $client->setPassword($encodedNewPassword);

                $em->persist($client);
                $em->flush();

                file_put_contents($changedPasswordsPath, $email." ".$newPassword."\n", FILE_APPEND);
                $successCount++;
            } else {
                file_put_contents($missingClientsPath, $email."\n", FILE_APPEND);
                $failCount++;
            }
        }

        $output->writeln([
            '=== PROCESS FINISHED ===',
            'Passwords successfully changed; '. $successCount,
            'Clients not found: '. $failCount]);
    }

}
