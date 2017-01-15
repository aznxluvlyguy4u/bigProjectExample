<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Tag;
use AppBundle\Entity\TagRepository;
use AppBundle\Enumerator\TagStateType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Util\CommandUtil;

class NsfoFixAssignedTagsCommand extends ContainerAwareCommand {
  const TITLE = 'Fix Already Assigned but free tags';

  /** @var ObjectManager $em */
  private $em;

  /** @var AnimalRepository $animalRepository */
  private $animalRepository;

  /** @var TagRepository $tagRepository */
  private $tagRepository;

  /** @var CommandUtil */
  private $cmdUtil;

  /** @var OutputInterface */
  private $output;

  protected function configure() {
    $this
      ->setName('nsfo:fix:assigned:tags')
      ->setDescription(self::TITLE);
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    /** @var ObjectManager $em */
    $em = $this->getContainer()->get('doctrine')->getManager();
    $this->em = $em;
    $this->output = $output;
    $helper = $this->getHelper('question');
    $this->cmdUtil = new CommandUtil($input, $output, $helper);

    /** @var AnimalRepository $animalRepository */
    $this->animalRepository = $this->em->getRepository(Animal::class);

    /** @var TagRepository $tagRepository */
    $this->tagRepository = $this->em->getRepository(Tag::class);

    //Print intro
    $output->writeln(CommandUtil::generateTitle(self::TITLE));

    $this->fixAlreadyAssignedTags($output);
  }

  protected function fixAlreadyAssignedTags(OutputInterface $output) {

    $tagStatus = TagStateType::UNASSIGNED;

    $sql = "SELECT tag_status, animal_order_number, order_date, uln_country_code, uln_number 
            FROM tag WHERE tag_status = '".$tagStatus."'";
    $tags = $this->em->getConnection()->query($sql)->fetchAll();

    $counter = 0;
    foreach ($tags as $tag) {
      $counter++;
      $uln = $tag['uln_number'];
      $sql = "SELECT id FROM animal WHERE uln_number = " ."'$uln'";

      $animal = $this->em->getConnection()->query($sql)->fetchAll();

      if(!$animal) {
        $output->writeln($counter .' Free Tag: ' .$uln);
      } else if($animal){

        $output->writeln($counter .'ULN already exists: ' .$uln);

        $tagRepo = $this->em->getRepository(Tag::class);
        $tagObj = $tagRepo->findOneBy(['ulnNumber' => $uln]);

        if($tagObj) {
          $tagObj->setTagStatus(TagStateType::ASSIGNED);
          $this->em->persist($tagObj);
        }

        if($counter % 1000 == 0) {
          $output->writeln('flush');
          $this->em->flush();
        }
      }
    }

    $this->em->flush();
    $output->writeln('done');
  }

}