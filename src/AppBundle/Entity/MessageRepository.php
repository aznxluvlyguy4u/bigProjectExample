<?php

namespace AppBundle\Entity;

/**
 * Class MessageRepository
 * @package AppBundle\Entity
 */
class MessageRepository extends BaseRepository {

    /**
     * @param DeclareBase $declare
     * @return Message|null
     */
    public function findOneByRequest(DeclareBase $declare): ?Message
    {
        return $declare ? $this->findOneBy(['requestMessage' => $declare]) : null;
    }

    public function getNonInvoiceMessages(Client $client, Location $location) {
        $sql = "SELECT
                  receiver.last_name AS receiver_last_name,
                  receiver.first_name AS receiver_last_name,
                  receiver_location.ubn AS receiver_ubn,
                  receiver_company.company_name AS receiver_company,
                  sender.last_name AS sender_last_name,
                  sender.first_name AS sender_first_name,
                  sender_location.ubn AS sender_ubn,
                  sender_company.company_name AS sender_company,
                  message.message_id,
                  message.type,
                  message.subject,
                  message.message,
                  message.is_read,
                  message.creation_date,
                  message.is_hidden,
                  message.data,
                  declare_base_response.success_indicator
                FROM
                  message
                LEFT JOIN person AS receiver ON message.receiver_id = receiver.id
                LEFT JOIN person AS sender ON message.sender_id = sender.id
                LEFT JOIN location AS receiver_location ON message.receiver_location_id = receiver_location.id
                LEFT JOIN location AS sender_location ON message.sender_location_id = sender_location.id
                LEFT JOIN company AS receiver_company ON receiver_location.company_id = receiver_company.id
                LEFT JOIN company AS sender_company ON sender_location.company_id = sender_company.id
                INNER JOIN declare_base_response ON message.declare_base_response_id = declare_base_response.id
                WHERE
                    message.receiver_id = " . $client->getId() . " OR message.receiver_location_id = '" . $location->getId() . "'";
        return $this->getManager()->getConnection()->query($sql)->fetchAll();
    }

    public function getInvoiceMessages(Client $client, Location $location) {
        $invoiceSql = "SELECT
                  receiver.last_name AS receiver_last_name,
                  receiver.first_name AS receiver_last_name,
                  receiver_location.ubn AS receiver_ubn,
                  receiver_company.company_name AS receiver_company,
                  sender.last_name AS sender_last_name,
                  sender.first_name AS sender_first_name,
                  sender_location.ubn AS sender_ubn,
                  sender_company.company_name AS sender_company,
                  message.message_id,
                  message.type,
                  message.subject,
                  message.message,
                  message.is_read,
                  message.creation_date,
                  message.is_hidden,
                  message.data
                FROM
                  message
                LEFT JOIN person AS receiver ON message.receiver_id = receiver.id
                LEFT JOIN person AS sender ON message.sender_id = sender.id
                LEFT JOIN location AS receiver_location ON message.receiver_location_id = receiver_location.id
                LEFT JOIN location AS sender_location ON message.sender_location_id = sender_location.id
                LEFT JOIN company AS receiver_company ON receiver_location.company_id = receiver_company.id
                LEFT JOIN company AS sender_company ON sender_location.company_id = sender_company.id
                WHERE
                    message.type = 'NEW_INVOICE' AND (message.receiver_id = " . $client->getId() . " OR message.receiver_location_id = '" . $location->getId() . "')";

        return $this->getManager()->getConnection()->query($invoiceSql)->fetchAll();
    }
}