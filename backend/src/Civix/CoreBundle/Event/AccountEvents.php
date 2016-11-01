<?php
namespace Civix\CoreBundle\Event;

class AccountEvents
{
    const PRE_CREATE = 'account.pre_create';
    const BANK_ACCOUNT_PRE_CREATE = 'bank_account.pre_create';
    const BANK_ACCOUNT_PRE_DELETE = 'bank_account.pre_delete';
}