<?php

namespace Canvastack\Canvastack\Database\Migrations;

use Canvastack\Canvastack\Database\Migrations\Tables\BaseTables;
use Canvastack\Canvastack\Database\Migrations\Tables\MasjidTables;
use Canvastack\Canvastack\Database\Migrations\Tables\ShopTables;

/**
 * Created on Dec 17, 2022
 *
 * Time Created : 3:21:08 AM
 * Filename     : Process.php
 *
 * @filesource Process.php
 *
 * @author     wisnuwidi @Incodiy - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
class Process extends Config
{
    public $exclude = [];

    private $initialize;

    private $initializer = ['base', 'platforms', 'shop'];

    private $queryObject = [];

    public function __construct()
    {
        $this->queryObject = [
            'base' => new BaseTables(),
            'platforms' => new MasjidTables(),
            'shop' => new ShopTables(),
        ];

        $this->init($this->exclude);
    }

    private function init()
    {

        if (! empty($this->exclude)) {
            $process = array_diff($this->initializer, $this->exclude);
            foreach ($process as $initialize) {
                $this->initialize[$initialize] = $this->queryObject[$initialize];
            }
        } else {
            $this->initialize['base'] = new BaseTables();
            $this->initialize['platforms'] = new MasjidTables();
            $this->initialize['shop'] = new ShopTables();
        }
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! empty($this->exclude)) {
            $process = array_diff($this->initializer, $this->exclude);
            foreach ($process as $initialize) {
                $this->initialize[$initialize]->up();
            }
        } else {
            $this->initialize['base']->up();
            $this->initialize['platforms']->up();
            $this->initialize['shop']->up();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! empty($this->exclude)) {
            $process = array_diff($this->initializer, $this->exclude);
            foreach ($process as $initialize) {
                $this->initialize[$initialize]->drop();
            }
            $this->initialize['base']->last_drop();

        } else {
            $this->initialize['base']->drop();
            $this->initialize['platforms']->drop();
            $this->initialize['shop']->drop();
            $this->initialize['base']->last_drop();
        }
    }
}
