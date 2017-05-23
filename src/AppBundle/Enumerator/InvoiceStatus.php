<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 23-5-17
 * Time: 9:52
 */

namespace AppBundle\Enumerator;


class InvoiceStatus
{
 const INCOMPLETE = "INCOMPLETE";
 const CANCELLED = "CANCELLED";
 const PAID = "PAID";
 const UNPAID = "UNPAID";
 const NOT_SEND = "NOT SEND";
}