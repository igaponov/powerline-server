<?php
namespace Civix\CoreBundle\Event;

class CustomerEvents
{
    const PRE_CREATE = 'customer.create';
    const CARD_PRE_CREATE = 'card.pre_create';
    const CARD_PRE_DELETE = 'card.pre_delete';
}