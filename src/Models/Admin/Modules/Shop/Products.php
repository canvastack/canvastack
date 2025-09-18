<?php

namespace Canvastack\Canvastack\Models\Admin\Modules\Shop;

use Canvastack\Canvastack\Core\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Created on Dec 17, 2022
 *
 * Time Created : 5:48:17 PM
 * Filename     : Products.php
 *
 * @filesource Products.php
 *
 * @author     wisnuwidi @Incodiy - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
class Products extends Model
{
    use SoftDeletes;

    protected $table = 'shop_product';

    protected $guarded = [];

    public function category()
    {
        return $this->belongsToMany(Category::class, 'shop_product_category', 'product_id', 'category_id');
    }

    public function relation_category()
    {
        return $this->belongsToMany(Category::class, 'shop_product_category', 'product_id', 'category_id');
    }

    public function categoryInfo()
    {
        foreach ($this->category as $categoryInfo) {
            return $categoryInfo->getAttributes();
        }
    }
}
