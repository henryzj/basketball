<?php

class Dao_Core_V2HotelTag extends Dao_Core_Abstract
{
    protected $_tableName = 'v2_hotel_tag';
    protected $_pk        = ['hotel_id', 'tag_id'];
}