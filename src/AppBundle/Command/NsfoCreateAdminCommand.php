<?php

namespace AppBundle\Command;

use AppBundle\Component\Utils;
use AppBundle\Entity\Employee;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoCreateAdminCommand extends ContainerAwareCommand
{
    const TITLE = 'Create a new SUPER ADMIN';

    /** @var ObjectManager $em */
    private $em;

    protected function configure()
    {
        $this
            ->setName('nsfo:create:admin')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $cmdUtil->setStartTimeAndPrintIt();
        
        $randomNumberLength = 3;
        $randomCharacters = '0123456789';
        $randomNumber = Utils::randomString($randomNumberLength, $randomCharacters);

        $firstName = $cmdUtil->generateQuestion('Insert first name', 'John'.$randomNumber);
        $lastName = $cmdUtil->generateQuestion('Insert last name', 'Doe'.$randomNumber);
        $emailAddress = $cmdUtil->generateQuestion('Insert email address', 'test'.$randomNumber.'@email.com');
        $password = $cmdUtil->generateQuestion('Insert password (default: 12345)', '12345');

        $output->writeln([
            'Your new SUPER ADMIN account data:',
            'FirstName: '.$firstName,
            'LastName: '.$lastName,
            'EmailAddress: '. $emailAddress,
            'Password: '.$password,
            '(verify the input)',
            '']);
        
        $isCreateNewSuperAdmin = $cmdUtil->generateConfirmationQuestion('Create the new SUPER ADMIN account? (y/n)');

        if($isCreateNewSuperAdmin) {

            // Create new admin
            $newAdmin = new Employee(AccessLevelType::SUPER_ADMIN, $firstName, $lastName, $emailAddress);
            $encoder = $this->getContainer()->get('security.password_encoder');
            $encodedNewPassword = $encoder->encodePassword($newAdmin, $password);
            $newAdmin->setPassword($encodedNewPassword);

            $em->persist($newAdmin);
            $em->flush();

            $output->writeln([
                '- SUPER ADMIN Account created! -',
                'total admin count: '.$this->totalAdminCount(),
                '']);

        } else {
            $output->writeln([
                '- Aborted -',
                'total admin count: '.$this->totalAdminCount(),
                '']);
        }

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }

    protected function totalAdminCount()
    {
        $admins = $this->em->getRepository(Employee::class)->findAll();
        return sizeof($admins);
    }

}
