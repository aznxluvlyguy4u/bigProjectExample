<?php

namespace AppBundle\Output;

use AppBundle\Entity\Company;
use AppBundle\Entity\CompanyNote;

class CompanyNoteOutput
{
    /**
     * @param Company $company
     *
     * @return array
     */
    public static function createNotes($company)
    {
        $notes = $company->getNotes();
        $res = [];
        foreach ($notes as $note) {
            /**
             * @var CompanyNote $note
             */
            $newNote = array();
            $newNote['creation_date'] = $note->getCreationDate();
            $newNote['creator']['first_name'] = $note->getCreator()->getFirstName();
            $newNote['creator']['last_name'] = $note->getCreator()->getLastName();
            $newNote['message'] = $note->getNote();
            $res[] = $newNote;
        }
        return $res;
    }

    /**
     * @param CompanyNote $note
     *
     * @return array
     */
    public static function createNoteResponse($note)
    {
        $newNote = array();
        $newNote['creation_date'] = $note->getCreationDate();
        $newNote['creator']['first_name'] = $note->getCreator()->getFirstName();
        $newNote['creator']['last_name'] = $note->getCreator()->getLastName();
        $newNote['message'] = $note->getNote();
        return $newNote;
    }

}