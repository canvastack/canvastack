<?php

namespace Canvastack\Canvastack\Controllers\Admin\Modules\Shop;

use Canvastack\Canvastack\Controllers\Core\Controller;
use Canvastack\Canvastack\Models\Admin\Modules\Shop\Products;

/**
 * Created on Dec 17, 2022
 *
 * Time Created : 5:45:51 PM
 * Filename     : ProductController.php
 *
 * @filesource ProductController.php
 *
 * @author     wisnuwidi @Incodiy - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
class ProductController extends Controller
{
    private $fields = [
        'name:Product Name',
        'description:Product Description',
        'price',
        'category',
        'active',
    ];

    public function __construct()
    {
        parent::__construct(Products::class, 'modules.shop.product');
    }

    private static function key_relations()
    {
        return [
            'shop_product_category.product_id' => 'shop_product.id',
            'shop_category.id' => 'shop_product_category.category_id',
        ];
    }

    public function index()
    {
        $this->setPage('Product');

        $this->table->searchable();
        $this->table->clickable();
        $this->table->sortable();

        $this->table->relations($this->model, 'relation_category', 'category', self::key_relations());

        $this->table->lists($this->model_table, $this->fields);

        return $this->render();
    }
}
