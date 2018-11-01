<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareTagReplaceResponse;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\TagStateType;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class DeclareTagReplaceProcessor extends DeclareProcessorBase implements DeclareTagReplaceProcessorInterface
{
    /** @var DeclareTagReplace */
    private $tagReplace;
    /** @var DeclareTagReplaceResponse */
    private $response;
    /** @var Animal */
    private $animal;

    /** @var string */
    private $errorMessage;
    /** @var string */
    private $errorCode;


    function process(DeclareTagReplace $tagReplace)
    {
        $this->getManager()->persist($tagReplace);
        $this->getManager()->flush();
        $this->getManager()->refresh($tagReplace);

        $this->tagReplace = $tagReplace;

        $this->response = new DeclareTagReplaceResponse();
        $this->response->setDeclareTagReplaceIncludingAllValues($this->tagReplace);

        $status = $this->getRequestStateAndSetErrorData();

        switch ($status) {
            case RequestStateType::FINISHED:
                $this->processSuccessLogic();
                break;

            case RequestStateType::FAILED:
                $this->processFailedLogic();
                break;

            default: throw new PreconditionFailedHttpException('Invalid requestState: '.$status);
        }

        $this->persistResponseInSeparateTransaction($this->response);

        $this->getManager()->persist($this->tagReplace);
        $this->getManager()->flush();

        $this->clearLivestockCacheForLocation($this->tagReplace->getLocation());

        $this->animal = null;
        $this->tagReplace = null;
        $this->response = null;
        $this->errorCode = null;
        $this->errorMessage = null;

        return $this->getDeclareMessageArray($tagReplace, false);
    }


    private function getRequestStateAndSetErrorData()
    {
        // Validation is done in the TagReplaceService

        $this->errorMessage = '';
        $this->errorCode = '';

        $this->response->setSuccessValues();
        return RequestStateType::FINISHED;
    }


    private function processSuccessLogic()
    {
        $this->animal = $this->getManager()->getRepository(Animal::class)
            ->findByUlnCountryCodeAndNumber(
                $this->tagReplace->getUlnCountryCodeToReplace(),
                $this->tagReplace->getUlnNumberToReplace()
            );

        if ($this->animal) {
            $this->animal->setUlnCountryCode($this->tagReplace->getUlnCountryCodeReplacement());
            $this->animal->setUlnNumber($this->tagReplace->getUlnNumberReplacement());
            $this->animal->setAnimalOrderNumber($this->tagReplace->getAnimalOrderNumberReplacement());

            $replacingTag = $this->getReplacingTag();
            if ($replacingTag) {
                $replacingTag->setUlnCountryCode($this->tagReplace->getUlnCountryCodeToReplace());
                $replacingTag->setUlnNumber($this->tagReplace->getUlnNumberToReplace());
                $replacingTag->setAnimalOrderNumber($this->tagReplace->getAnimalOrderNumberToReplace());
                $replacingTag->setTagStatus(TagStateType::REPLACED);
                $this->animal->addUlnHistory($replacingTag);
                $this->getManager()->persist($replacingTag);
            }

            $this->getManager()->persist($this->animal);
            $this->tagReplace->setAnimal($this->animal);
        }

        $this->tagReplace->setFinishedRequestState();
    }


    private function processFailedLogic()
    {
        $replacingTag = $this->getReplacingTag();

        if ($replacingTag) {
            $replacingTag->unassignTag();
            $this->getManager()->persist($replacingTag);
        }

        $this->tagReplace->setFailedRequestState();
    }


    /**
     * @return Tag|null
     */
    private function getReplacingTag(): ?Tag
    {
        return $this->getManager()->getRepository(Tag::class)
            ->findByUln(
                $this->tagReplace->getUlnCountryCodeReplacement(),
                $this->tagReplace->getUlnNumberReplacement()
            );
    }

}