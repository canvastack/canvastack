<?php

namespace Canvastack\Canvastack\Database\Seeders\Includes\App;

use Illuminate\Support\Facades\DB;

/**
 * Created on Dec 12, 2022
 *
 * Time Created : 2:16:26 PM
 *
 * @filesource	Groups.php
 *
 * @author     wisnuwidi@canvastack.com - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
trait Groups
{
    /**
     * Insert Group
     *
     * SELECT
            role_type,
            region,
            CONCAT(
                "DB::table('base_group')->insert(['group_name' => '",
                REPLACE(lower(CONCAT(role_type, '', region)), ' ', ''),
                "', 'group_info' => '", CONCAT(role_type, ' ', region),
                "', 'active' => 1]);") _query
        FROM `user_data_regional_keren` GROUP BY 1, 2 ORDER BY 1, 2;
     */
    private function insertGroups()
    {
        DB::table('base_group')->insert(['group_name' => 'sales.reporting', 'group_info' => 'Sales Reporting', 'group_alias' => 'National', 'active' => 1]);
        DB::table('base_group')->insert(['group_name' => 'customer.analytics', 'group_info' => 'Customer Analytics', 'group_alias' => 'National', 'active' => 1]);
    }

    private static function getQueryInfo($tablename, $fieldLabel, $fieldValue)
    {
        $data = DB::select("SELECT DISTINCT {$fieldLabel}, {$fieldValue} FROM {$tablename}");
        $result = [];
        foreach ($data as $row) {
            $result[$row->{$fieldLabel}] = $row->{$fieldValue};
        }

        return $result;
    }

    /**
     * SELECT
            email,
            REPLACE(lower(CONCAT(role_type, '', region)), ' ', '') role,
            CONCAT("
                DB::table('base_user_group')->insert(['user_id'	=> $userInfo['", email ,"'], 'group_id' => $groupInfo['", REPLACE(lower(CONCAT(role_type, '', region)), ' ', '') ,"']]);
            ")
        FROM user_data_regional_keren
        GROUP BY email
     */
    private function insertUserGroup()
    {
        $groupInfo = self::getQueryInfo('base_group', 'group_name', 'id');
        $userInfo = self::getQueryInfo('users', 'email', 'id');

        DB::table('base_user_group')->insert(['user_id' => $userInfo['sales.reporting@smartfren.com'], 'group_id' => $groupInfo['sales.reporting']]);
        DB::table('base_user_group')->insert(['user_id' => $userInfo['customer.analytics@smartfren.com'], 'group_id' => $groupInfo['customer.analytics']]);
    }
}
