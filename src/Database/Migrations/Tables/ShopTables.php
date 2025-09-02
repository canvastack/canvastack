<?php

namespace Canvastack\Canvastack\Database\Migrations\Tables;

use Canvastack\Canvastack\Database\Migrations\Config;
use Illuminate\Database\Schema\Blueprint;

/**
 * Created on Dec 17, 2022
 *
 * Time Created : 4:33:14 AM
 * Filename     : ShopTables.php
 *
 * @filesource ShopTables.php
 *
 * @author    wisnuwidi @Incodiy - 2022
 * @copyright wisnuwidi
 *
 * @email     wisnuwidi@canvastack.com
 */
class ShopTables extends Config
{
    public function __construct()
    {
        $this->schema();
    }

    public function up()
    {
        $this->main_tables();
        $this->relation_tables();
    }

    public function drop()
    {
        $this->schema::dropIfExists('shop_transactions');

        $this->schema::dropIfExists('shop_product_category');
        $this->schema::dropIfExists('shop_stock');
        $this->schema::dropIfExists('shop_discount');
        $this->schema::dropIfExists('shop_tax');
        $this->schema::dropIfExists('shop_whitelist');

        $this->schema::dropIfExists('shop_product');
        $this->schema::dropIfExists('shop_category');
        $this->schema::dropIfExists('shop_payment_method');
        $this->schema::dropIfExists('shop_shiping_method');
    }

    private function main_tables()
    {
        // PRODUCTS
        $this->schema::create('shop_product', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->bigInteger('id', true)->unsigned();

            $table->string('name', 300)->nullable();
            $table->text('product_image')->nullable();
            $table->text('product_image_thumb')->nullable();
            $table->text('description')->nullable();
            $table->text('product_gallery')->nullable();
            $table->text('product_gallery_thumb')->nullable();
            $table->bigInteger('price')->nullable();

            $table->bigInteger('created_by');
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->softDeletes();
            $table->smallInteger('active')->default(0);

            $table->index('name');
            $table->index('active');
        });

        // CATEGORY
        $this->schema::create('shop_category', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();

            $table->string('category', 300)->nullable();
            $table->string('description', 300)->nullable();

            $table->bigInteger('created_by');
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->softDeletes();
            $table->smallInteger('active')->default(0);

            $table->index('category');
            $table->index('active');
        });

        // PAYMENT METHOD
        $this->schema::create('shop_payment_method', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();

            $table->string('payment_method', 300)->nullable();
            $table->bigInteger('price_method')->nullable();
            $table->string('discount_method', 50)->nullable();
            $table->string('account_name', 300)->nullable();
            $table->string('account_digits', 50)->nullable();

            $table->bigInteger('created_by');
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->softDeletes();
            $table->smallInteger('active')->default(0);

            $table->index('payment_method');
            $table->index('active');
        });

        // SHIPING METHOD
        $this->schema::create('shop_shiping_method', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();

            $table->string('shiping_method', 300)->nullable();
            $table->string('shiping_estimation', 300)->nullable();
            $table->string('shiping_price', 300)->nullable();
            $table->string('shiping_discount', 300)->nullable();

            $table->bigInteger('created_by');
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->softDeletes();
            $table->smallInteger('active')->default(0);

            $table->index('shiping_method');
            $table->index('active');
        });
    }

    private function relation_tables()
    {
        // PRODUCT CATEGORY
        $this->schema::create('shop_product_category', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();

            $table->bigInteger('product_id')->unsigned();
            $table->integer('category_id')->unsigned();

            $table->index('product_id');
            $table->index('category_id');

            $table->foreign('product_id')->references('id')->on('shop_product')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('shop_category')->onUpdate('cascade')->onDelete('cascade');
        });

        // PRODUCT STOCK
        $this->schema::create('shop_stock', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();

            $table->bigInteger('product_id')->unsigned();
            $table->integer('stock')->unsigned();

            $table->index('product_id');

            $table->foreign('product_id')->references('id')->on('shop_product')->onUpdate('cascade')->onDelete('cascade');
        });

        // PRODUCT DISCOUNT
        $this->schema::create('shop_discount', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();

            $table->bigInteger('product_id')->unsigned();
            $table->integer('discount')->unsigned();
            $table->smallInteger('active')->default(0);

            $table->index('product_id');

            $table->foreign('product_id')->references('id')->on('shop_product')->onUpdate('cascade')->onDelete('cascade');
        });

        // PRODUCT TAX
        $this->schema::create('shop_tax', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();

            $table->bigInteger('product_id')->unsigned();
            $table->integer('tax')->unsigned();
            $table->smallInteger('active')->default(0);

            $table->index('product_id');

            $table->foreign('product_id')->references('id')->on('shop_product')->onUpdate('cascade')->onDelete('cascade');
        });

        // PRODUCT WHITELISTS
        $this->schema::create('shop_whitelist', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();

            $table->bigInteger('product_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->smallInteger('active')->default(0);

            $table->index('product_id');
            $table->index('user_id');

            $table->foreign('product_id')->references('id')->on('shop_product')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
        });

        // PRODUCT TRANSACTIONS
        $this->schema::create('shop_transactions', function (Blueprint $table) {
            $this->set_engine($table, $this->setEngine);

            $table->increments('id')->unsigned();

            $table->bigInteger('product_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->integer('quantity')->unsigned();
            $table->integer('payment_method_id')->unsigned();
            $table->integer('shiping_method_id')->unsigned();
            $table->integer('discount_id')->unsigned();
            $table->integer('tax_id')->unsigned();

            $table->timestamps();
            $table->softDeletes();

            $table->smallInteger('active')->default(0);

            $table->index('product_id');
            $table->index('user_id');

            $table->foreign('product_id')->references('id')->on('shop_product')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');

            $table->foreign('payment_method_id')->references('id')->on('shop_payment_method')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('shiping_method_id')->references('id')->on('shop_shiping_method')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('discount_id')->references('id')->on('shop_discount')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('tax_id')->references('id')->on('shop_tax')->onUpdate('cascade')->onDelete('cascade');
        });
    }
}
