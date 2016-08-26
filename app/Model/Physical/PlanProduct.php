<?php

class Model_Physical_PlanProduct extends Model_Base
{

    const TABLE_NAME = 't_physical_plan_product';

    public static function getPlanProduct ($iPlanID)
    {
    	return self::getAll([
            'where' => [
                'iPlanID' => $iPlanID,
                'iStatus' => 1
            ]
        ]);
    }

}