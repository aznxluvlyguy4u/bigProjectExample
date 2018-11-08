<?php

namespace AppBundle\Enumerator;


class MessageType
{
    const USER = 'USER';
    const DECLARE_ARRIVAL = 'DECLARE_ARRIVAL';
    const DECLARE_DEPART = 'DECLARE_DEPART';
    const DECLARE_MATE = 'DECLARE_MATE';
    const NEW_INVOICE = 'NEW_INVOICE';
    const NOTIFICATION_MESSAGE_SUFFIX = '_NOTIFICATION_MESSAGE';
}