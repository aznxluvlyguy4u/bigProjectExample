<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Tag;
use AppBundle\Entity\TagRepository;
use AppBundle\Enumerator\TagStateType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Util\CommandUtil;

class NsfoFixAssignedTagsCommand extends ContainerAwareCommand {
  const TITLE = 'Fix Already Assigned but free tags';

  /** @var ObjectManager $em */
  private $em;

  /** @var Connection $conn */
  private $conn;

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
    $this->conn = $em->getConnection();
    $this->output = $output;
    $helper = $this->getHelper('question');
    $this->cmdUtil = new CommandUtil($input, $output, $helper);

    /** @var AnimalRepository $animalRepository */
    $this->animalRepository = $this->em->getRepository(Animal::class);

    /** @var TagRepository $tagRepository */
    $this->tagRepository = $this->em->getRepository(Tag::class);

    //Print intro
    $output->writeln(CommandUtil::generateTitle(self::TITLE));

    $this->fixAlreadyAssignedTagsBySql();
  }


  /**
   * @throws \Doctrine\DBAL\DBALException
   */
  protected function fixAlreadyAssignedTagsBySql() {

    $tagStatus = TagStateType::UNASSIGNED;

    $sql = "SELECT CONCAT(t.uln_country_code,' ',t.uln_number) as uln
            FROM tag t
              INNER JOIN animal a ON a.uln_number = t.uln_number
            WHERE tag_status = '".$tagStatus."'";
    $tags = $this->conn->query($sql)->fetchAll();

    $totalTagsToUpdateCount = count($tags);
    if($totalTagsToUpdateCount == 0) {
      $this->output->writeln('All unassigned tags do not have any already existing animals! (no fix necessary)');
      return;
    }

    $this->output->writeln('Updating incorrect tag statuses from UNASSIGNED to ASSIGNED...');
    $sql = "UPDATE tag SET tag_status = '".TagStateType::ASSIGNED."'
            WHERE id IN(
              SELECT t.id
              FROM tag t
                INNER JOIN animal a ON a.uln_number = t.uln_number
              WHERE tag_status = '".$tagStatus."'
            )";
    $this->conn->exec($sql);
    $this->output->writeln('The following tags have been updated: ');

    foreach ($tags as $tag) {
      $this->output->writeln($tag['uln']);
    }
    $this->output->writeln('Update done for '.$totalTagsToUpdateCount.' tags.');
  }



  protected function fixAlreadyAssignedTags(OutputInterface $output) {

    $tagStatus = TagStateType::UNASSIGNED;

    $sql = "SELECT tag_status, animal_order_number, order_date, uln_country_code, uln_number 
            FROM tag WHERE tag_status = '".$tagStatus."'";
    $tags = $this->conn->query($sql)->fetchAll();

    $counter = 0;
    foreach ($tags as $tag) {
      $counter++;
      $uln = $tag['uln_number'];
      $sql = "SELECT id FROM animal WHERE uln_number = " ."'$uln'";

      $animal = $this->conn->query($sql)->fetchAll();

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