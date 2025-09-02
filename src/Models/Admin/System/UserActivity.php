<?php

namespace Canvastack\Canvastack\Models\Admin\System;

use Canvastack\Canvastack\Models\Core\Model;

/**
 * Created on Jun 9, 2023
 *
 * Time Created : 2:11:03 PM
 *
 * @filesource  UserActivity.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class UserActivity extends Model
{
    protected $table = 'temp_montly_activity';

    protected $guarded = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function montly_activity()
    {
        $sql = "
			SELECT
				monthly_activity.monthly_activity,
				monthly_activity.group_id,
				monthly_activity.user_id,
				
				d.group_info role_group,
				d.group_alias role_location,
				
				b.username,
				b.fullname,
				monthly_activity.user_email,
				
				monthly_activity.first_access,
				monthly_activity.last_access,
				monthly_activity.online_duration,
				monthly_activity.offline_duration,
				monthly_activity.login_counters,
				monthly_activity.logout_counters,
				monthly_activity.hit_activity,
				CASE
					WHEN b.active = 0 THEN 'Disabled'
					ELSE 'Active'
				END user_status
			FROM (
				SELECT
					LEFT(daily_activity, 7) monthly_activity,
					group_id,
					user_id,
					user_email,
					MIN(first_access) first_access,
					MAX(last_access) last_access,
					CONCAT( 
						FLOOR(TIME_FORMAT(SEC_TO_TIME(SUM(online_duration)), '%H') / 24), ' Days ',
						FLOOR( MOD(SUM(online_duration), 3600*24) / 3600), ' Hours ',
						FLOOR( MOD(SUM(online_duration), 3600) / 60), ' Minutes ',
						MOD( SUM(online_duration), 60), ' Seconds')
					online_duration,
					offline_duration,
					SUM(login_counters) login_counters,
					SUM(logout_counters) logout_counters,
					SUM(hit_activity) hit_activity
				FROM (
					# GET DAILY DATA ACTIVITY
					SELECT
						LEFT(a.created_at, 10) daily_activity,
						a.user_group_id group_id,
						a.user_id,
						a.user_email,
						MIN(a.created_at) first_access,
						MAX(a.created_at) last_access,
						TIMESTAMPDIFF(SECOND, MIN(a.created_at), MAX(a.created_at)) AS online_duration,
						CASE
							WHEN LEFT(a.created_at, 10) >= LEFT(NOW(), 7)
								THEN 
									CONCAT (
										TIMESTAMPDIFF(DAY, MAX(a.created_at), NOW()), ' Days, ',
										FLOOR(MOD (TIMESTAMPDIFF(SECOND, MAX(a.created_at), NOW()), 3600*24) / 3600), ' Hours, ',
										FLOOR(MOD (TIMESTAMPDIFF(SECOND, MAX(a.created_at), NOW()), 3600) / 60), ' Minutes, ',
										FLOOR(MOD (TIMESTAMPDIFF(SECOND, MAX(a.created_at), NOW()), 60)), ' Seconds'
									)
							ELSE
								CONCAT (
									TIMESTAMPDIFF(DAY, MAX(a.created_at), LAST_DAY(LEFT(a.created_at, 10)) ), ' Days, ',
									FLOOR(MOD (TIMESTAMPDIFF(SECOND, MAX(a.created_at), CONCAT(LAST_DAY(LEFT(a.created_at, 10)), ' 23:59:59') ), 3600*24) / 3600), ' Hours, ',
									FLOOR(MOD (TIMESTAMPDIFF(SECOND, MAX(a.created_at), CONCAT(LAST_DAY(LEFT(a.created_at, 10)), ' 23:59:59') ), 3600) / 60), ' Minutes, ',
									FLOOR(MOD (TIMESTAMPDIFF(SECOND, MAX(a.created_at), CONCAT(LAST_DAY(LEFT(a.created_at, 10)), ' 23:59:59') ), 60)), ' Seconds'
								)
						END offline_duration,
						CONCAT(LAST_DAY(LEFT(a.created_at, 10)), ' 23:59:59') from_last_date,
						(
							SELECT COUNT(n.page_info) 
							FROM log_activities n 
							WHERE n.page_info = 'login_processor'
								AND LEFT(n.created_at, 10) = LEFT(a.created_at, 10)
								AND n.user_id = a.user_id 
						) login_counters,
						(
							SELECT COUNT(n.page_info) 
							FROM log_activities n 
							WHERE n.page_info = 'logout'
								AND LEFT(n.created_at, 10) = LEFT(a.created_at, 10)
								AND n.user_id = a.user_id 
						) logout_counters,
					COUNT(a.page_info) hit_activity
					FROM `log_activities` a
					WHERE user_id > 1 #AND a.page_info IN ('login_processor', 'logout')
					GROUP BY LEFT(a.created_at, 10), a.user_id, a.user_group_id, a.user_email
					ORDER BY LEFT(a.created_at, 10) DESC, a.user_id, a.user_group_id
				) monthly
				GROUP BY LEFT(daily_activity, 7), user_id, group_id, user_email
				ORDER BY LEFT(daily_activity, 7) DESC, user_id, group_id
			) monthly_activity
			LEFT JOIN mantra_web.users b ON monthly_activity.user_email = b.email
			LEFT JOIN mantra_web.base_user_group c ON c.user_id = b.id
			LEFT JOIN mantra_web.base_group d ON c.group_id = d.id AND d.id >= 4
			WHERE d.group_info IS NOT NULL AND b.username IS NOT NULL
			GROUP BY LEFT(monthly_activity.monthly_activity, 7), monthly_activity.user_id, monthly_activity.group_id
			ORDER BY LEFT(monthly_activity.monthly_activity, 7) DESC, monthly_activity.user_id, monthly_activity.group_id
		";

        canvastack_temp_table($this->table, $sql, false);
    }

    public function user_never_login()
    {
        $sql = "
			SELECT
				userinfo.user_id,
				userinfo.group_info,
				userinfo.group_alias,
				userinfo.username,
				userinfo.fullname,
				userinfo.email,				
				CASE
					WHEN userinfo.active = 0 THEN 'Disabled'
					ELSE 'Active'
				END user_status,
				userinfo.registered_date,
				CONCAT (
					TIMESTAMPDIFF(DAY, userinfo.registered_date, NOW()), ' Days, ',
					FLOOR(MOD (TIMESTAMPDIFF(SECOND, userinfo.registered_date, NOW()), 3600*24) / 3600), 'h ',
					FLOOR(MOD (TIMESTAMPDIFF(SECOND, userinfo.registered_date, NOW()), 3600) / 60), 'm ',
					FLOOR(MOD (TIMESTAMPDIFF(SECOND, userinfo.registered_date, NOW()), 60)), 's'
				) offline_duration
			FROM (
				SELECT
					u.id user_id,
					g.id group_id,
					g.group_name,
					g.group_info,
					g.group_alias,
					u.username,
					u.fullname,
					u.email,
					u.active,
					u.created_at registered_date
				FROM users u
				LEFT JOIN base_user_group umap ON umap.user_id = u.id
				LEFT JOIN base_group g ON g.id = umap.group_id
				GROUP BY u.id, g.group_name, g.group_info, g.group_alias, u.username, u.fullname, u.email, u.active, u.created_at
				ORDER BY u.created_at DESC, g.id, u.id, g.group_name, g.group_info, g.group_alias, u.username, u.fullname, u.email, u.active
			) userinfo
			WHERE userinfo.email NOT IN (SELECT DISTINCT user_email FROM `log_activities`)
		";

        canvastack_temp_table('temp_'.__FUNCTION__, $sql, false);
    }
}
