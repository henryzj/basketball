<?php

class Dao_Ucenter_AccountTicket extends Dao_Ucenter_Abstract
{
    protected $_tableName     = 'account_ticket';
    protected $_pk            = 'uid';
    protected $_getPkByFields = ['ticket'];

    public function getUidByTicket($ticket)
    {
        return $this->_getPkByField('ticket', $ticket);
    }
}