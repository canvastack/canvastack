<?php

namespace Canvastack\Canvastack\Controllers\Admin\Modules\Shop;

use Canvastack\Canvastack\Core\Controller;
use Canvastack\Canvastack\Models\Admin\Modules\Shop\Category;

/**
 * Created on Dec 17, 2022
 *
 * Time Created : 8:47:30 PM
 * Filename     : CategoryController.php
 *
 * @filesource CategoryController.php
 *
 * @author     wisnuwidi @Incodiy - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
class CategoryController extends Controller
{
    private $fields = [
        'category',
        'active',
    ];

    public function __construct()
    {
        parent::__construct(Category::class, 'modules.shop.category');
    }

    public function index()
    {
        $this->setPage('Category');

        $this->table->searchable();
        $this->table->clickable();
        $this->table->sortable();

        $this->table->lists($this->model_table, $this->fields);

        return $this->render();
    }
}
