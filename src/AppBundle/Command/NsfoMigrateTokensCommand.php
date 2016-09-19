<?php

namespace AppBundle\Command;

use AppBundle\Entity\Person;
use AppBundle\Entity\Token;
use AppBundle\Enumerator\TokenType;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoMigrateTokensCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:tokens')
            ->setDescription('Migrate tokencode to Token entity')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(CommandUtil::generateTitle('Migrating Tokens'));
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $persons = $em->getRepository(Person::class)->findAll();

        $count = 0;
        foreach ($persons as $person) {
            /** @var Person $person */
            if(sizeof($person->getTokens()) == 0) {
                $token = new Token(TokenType::ACCESS, $person->getAccessToken());
                $person->addToken($token);
                $token->setOwner($person);
                $em->persist($person);

                $count++;
                if($count %50 == 0) {
                    $em->flush();
                }
            }
        }
        $em->flush();

        $output->writeln($count.' tokens migrated');
        $output->writeln('=== FINISHED ===');
    }

}
