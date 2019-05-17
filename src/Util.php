<?php

namespace Iliuxu\Acm;

use Iliuxu\Acm\Exception\AcmException;

class Util
{
    public static function checkDataId($dataId)
    {
        if (!is_string($dataId)) {
            throw new AcmException('invalid dataId: ' . $dataId);
        }
    }

    public static function checkGroup($group)
    {
        if (!is_string($group)) {
            return 'DEFAULT_GROUP';
        } else {
            return $group;
        }
    }
}
