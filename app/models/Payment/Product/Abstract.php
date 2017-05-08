<?php

abstract class Model_Payment_Product_Abstract extends Core_Model_Abstract
{
    protected $_title;
    protected $_detailTpl;

    public function getTitle()
    {
        return $this->_title;
    }

    public function getDetailTpl()
    {
        return $this->_detailTpl;
    }

    public function preCreateOrder(array &$params)
    {

    }

    public function postFinishOrder(Model_Payment_Order $order)
    {

    }

    public function walletBalancePay(Model_User $user, array &$params)
    {

    }
}