<?php

class Model_Badword extends Model_Base
{

    const TABLE_NAME = 't_badword';

    /**
     * 取到所有的违禁词
     * @return Ambigous <number, multitype:multitype:, multitype:unknown >
     */
    public static function getBadwords ()
    {
        return self::getCol(array(
            'iStatus' => 1
        ), 'sWord');
    }
    
    /**
     * 跟据敏感词名称取得敏感词
     *
     * @param unknown $sWord
     * @return Ambigous <number, multitype:, mixed>
     */
    public static function getBadwordByWord ($sWord)
    {
    	return self::getRow(array(
    			'where' => array(
    				'sWord' => $sWord,
    				'iStatus' => 1
    			)
    	));
    }
}