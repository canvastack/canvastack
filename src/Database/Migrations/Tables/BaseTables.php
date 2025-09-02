<?php

namespace Canvastack\Canvastack\Database\Migrations\Tables;

use Canvastack\Canvastack\Database\Migrations\Config;
use Illuminate\Database\Schema\Blueprint;

/**
 * Created on Dec 17, 2022
 *
 * Time Created : 3:10:43 AM
 * Filename : BaseTables.php
 *
 * @filesource BaseTables.php
 *
 * @author    wisnuwidi @Incodiy - 2022
 * @copyright wisnuwidi
 *
 * @email     wisnuwidi@canvastack.com
 */
class BaseTables extends Config
{
    public function __construct()
    {
        $this->schema();
    }

    public function up()
    {
        if (true === $this->is_multiplatform) {
            $this->multiple_main_tables();
            $this->multiple_relation_tables();
        } else {
            $this->multiple_main_tables();
            $this->multiple_relation_tables();
        }
        $this->extract_transform_and_load_table();
    }

    public function drop()
    {
        // RELATIONAL
        $this->schema::dropIfExists('base_page_privilege');
        $this->schema::dropIfExists('base_group_privilege');
        $this->schema::dropIfExists('base_user_group');

        // BASE ( MAIN ) TABLES
        $this->schema::dropIfExists('base_module');
        $this->schema::dropIfExists('base_group');
        $this->schema::dropIfExists('base_language');
        $this->schema::dropIfExists('base_timezone');
        $this->schema::dropIfExists('base_postal_code');
        $this->schema::dropIfExists('base_preference');
        $this->schema::dropIfExists('base_icon');
        $this->schema::dropIfExists('base_maintenance');

        $this->schema::dropIfExists('base_extransload');
    }

    public function last_drop()
    {
        $this->schema::dropIfExists('users');
        $this->schema::dropIfExists('log_activities');
    }

    private function multiple_main_tables()
    {
        // Web Preference
        $this->schema::create('base_preference', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();

            // WEB IDENTITY
            $table->string('title', 30)->nullable();
            $table->string('sub_title', 150)->nullable();
            $table->text('logo')->nullable();
            $table->text('logo_thumb')->nullable();
            $table->text('header')->nullable();
            $table->text('footer')->nullable();
            $table->string('template', 150)->nullable();
            $table->string('language', 150)->nullable();
            $table->string('timezone', 150)->nullable();

            // SESSIONS
            $table->string('session_name', '100')->nullable();
            $table->integer('session_lifetime')->nullable();

            // SEO
            $table->string('meta_author', 50)->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_keywords')->nullable();
            $table->text('meta_description')->nullable();

            // CONTACT EMAIL
            $table->string('email_person', 100)->nullable();
            $table->string('email_address', 255)->nullable();

            // SMTP
            $table->string('smtp_host', 255)->nullable();
            $table->integer('smtp_port')->nullable();
            $table->smallInteger('smtp_secure')->nullable();
            $table->string('smtp_user', 255)->nullable();
            $table->string('smtp_password', 255)->nullable();

            // LOGIN PREFERENCES
            $table->string('login_title', 50)->nullable();
            $table->text('login_background')->nullable();
            $table->text('login_background_thumb')->nullable();
            $table->integer('login_attempts')->nullable();
            $table->smallInteger('change_password')->nullable();

            // MAINTENANCE
            $table->smallInteger('debug')->nullable();
            $table->smallInteger('maintenance')->nullable();
        });

        // Users Table
        $this->schema::create('users', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->bigInteger('id', true)->unsigned();

            $table->string('username', 150);
            $table->string('fullname', 150)->nullable();
            $table->string('alias', 150)->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->string('gender', 5)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place', 50)->nullable();
            $table->string('photo')->nullable();
            $table->string('photo_thumb')->nullable();
            $table->string('file_info')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('language', 10)->nullable();

            $table->string('timezone', 20)->nullable();
            $table->string('ip_address', 20)->unique()->nullable();
            $table->string('first_route', 100)->nullable();

            $table->dateTime('reg_date')->nullable();
            $table->dateTime('last_visit_date')->nullable();
            $table->dateTime('past_visit_date')->nullable();
            $table->rememberToken();

            $table->smallInteger('change_password')->nullable();
            $table->dateTime('last_change_password_date')->nullable();
            $table->dateTime('expire_date')->nullable();
            $table->text('cryptcode')->nullable();

            $table->bigInteger('created_by');
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->softDeletes();
            $table->smallInteger('active')->default(0);

            $table->index('username');
            $table->index('email');
            $table->index('cryptcode');
        });

        // Groups Table
        $this->schema::create('base_group', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();

            $table->string('group_name', 30);
            $table->text('group_alias')->nullable();
            $table->text('group_info')->nullable();
            if (true === $this->is_multiplatform) {
                $table->bigInteger($this->platform_key)->unsigned();
            }

            $table->smallInteger('active')->default(0);
            $table->softDeletes();

            $table->index('group_name');
            if (true === $this->is_multiplatform) {
                $table->index($this->platform_key);
            }
            if (true === $this->is_multiplatform) {
                $table->foreign($this->platform_key)->references('id')->on($this->platform_table)->onUpdate('cascade')->onDelete('cascade');
            }
        });

        // Module Table
        $this->schema::create('base_module', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();

            $table->string('route_path', 50);
            $table->string('parent_name', 50)->nullable();
            $table->string('module_name', 50)->nullable();
            $table->text('module_info')->nullable();
            $table->text('icon')->nullable();

            $table->string('menu_sort', 20)->nullable();
            $table->smallInteger('flag_status')->default(0);
            $table->smallInteger('active')->default(0);
            $table->softDeletes();
        });

        // Language Table
        $this->schema::create('base_language', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->string('abbr', 10)->nullable();
            $table->string('lang', 5)->nullable();
            $table->string('language', 150)->nullable();
            $table->string('charset', 10)->nullable();

            $table->primary('abbr');
            $table->index('lang');
        });

        // Timezone Table
        $this->schema::create('base_timezone', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();
            $table->string('timezone', 30)->nullable();
        });

        // Log Activities
        $this->schema::create('log_activities', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->bigIncrements('id')->unsigned();

            $table->integer('user_id');
            $table->string('username')->nullable();
            $table->string('user_fullname')->nullable();
            $table->string('user_email')->nullable();

            $table->string('user_group_id');
            $table->string('user_group_name')->nullable();
            $table->string('user_group_info')->nullable();

            $table->string('route_path')->nullable();
            $table->string('module_name')->nullable();
            $table->string('page_info')->nullable();
            $table->text('urli')->nullable();
            $table->string('method', 8)->nullable();

            $table->string('ip_address', 300)->nullable();
            $table->string('user_agent')->nullable();
            $table->text('sql_dump')->nullable();

            $table->index('user_id');
            $table->index('user_group_id');

            $table->timestamps();
        });

        // Icon
        $this->schema::create('base_icon', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->bigIncrements('id')->unsigned();
            $table->string('type');
            $table->string('tag');
            $table->string('class');
            $table->string('label');

            $table->smallInteger('active')->default(0);
            $table->softDeletes();

            $table->timestamps();
        });

        // POSTAL CODE TABLE
        $this->schema::create('base_postal_code', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();

            // WEB IDENTITY
            $table->string('province', 80);
            $table->string('regency', 100)->nullable();
            $table->string('sub_district', 100)->nullable();
            $table->string('urban_village', 100)->nullable();
            $table->string('postal_code', 5)->nullable();

            $table->index('postal_code');
            $table->timestamps();
        });

        // MAINTENANCE
        $this->schema::create('base_maintenance', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();
            $table->string('title', 100);
            $table->text('description');
            $table->text('logo')->nullable();
            $table->text('logo_thumb')->nullable();
            $table->text('image')->nullable();
            $table->text('image_thumb')->nullable();
            $table->string('time_duration', 100);
            $table->smallInteger('subscribe_button')->default(0);
            $table->text('subscribe_text')->nullable();
            $table->smallInteger('status')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function multiple_relation_tables()
    {
        // User Group Table
        $this->schema::create('base_user_group', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->bigInteger('id', true)->unsigned();

            $table->bigInteger('user_id')->unsigned();
            $table->integer('group_id')->unsigned();

            $table->index('user_id');
            $table->index('group_id');

            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('base_group')->onUpdate('cascade')->onDelete('cascade');
        });

        // Group Privilege Table
        $this->schema::create('base_group_privilege', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->bigInteger('id', true)->unsigned();

            $table->integer('group_id')->unsigned();
            $table->integer('module_id')->unsigned();

            $table->string('admin_privilege', 7)->nullable();
            $table->string('index_privilege', 7)->nullable();

            $table->index('group_id');
            $table->index('module_id');

            $table->foreign('group_id')->references('id')->on('base_group')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('base_module')->onUpdate('cascade')->onDelete('cascade');
        });

        // Mapping Page Privilege Table
        $this->schema::create('base_page_privilege', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->bigInteger('id', true)->unsigned();

            $table->integer('group_id')->unsigned();
            $table->integer('module_id')->unsigned();

            $table->string('target_table', 150)->nullable();
            $table->string('target_field_name', 50)->nullable();
            $table->text('target_field_values')->nullable();

            $table->index('group_id');
            $table->index('module_id');

            $table->foreign('group_id')->references('id')->on('base_group')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('base_module')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    private function extract_transform_and_load_table()
    {
        // EXTRACT-TRANSFORM-&-LOAD TABLE
        $this->schema::create('base_extransload', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->bigIncrements('id', true)->unsigned();

            $table->string('process_name', 300);
            $table->text('remarks')->nullable();
            $table->string('source_connection_name', 80);
            $table->string('source_table_name', 200);
            $table->integer('source_data_counts')->nullable()->default(0);

            $table->string('target_connection_name', 80);
            $table->string('target_table_name', 200);
            $table->integer('target_current_counts')->nullable()->default(0);

            $table->integer('success_data_transfers')->nullable()->default(0);

            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('source_table_name');
            $table->index('target_table_name');
        });
    }
}
