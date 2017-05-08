<?php

interface Model_Account_Third_Interface
{
    public function getThirdUser();

    public function bindUidByThird($uid, array $thirdUser, $upAccountInfo = true);
}