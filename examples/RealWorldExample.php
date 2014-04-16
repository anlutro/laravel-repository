<?php
/**
 * This is a real repository from one of my real world projects. I will not
 * bother explaining too much what is going on in it, but it might serve as an
 * example of the type of things you can do.
 */

namespace REDACTED\Wastage;

use anlutro\LaravelRepository\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;

use REDACTED\Departments\DepartmentModel;
use REDACTED\Products\ProductModel;

class ItemRepository extends EloquentRepository
{
    protected $wastage;
    protected $department;
    protected $product;

    public function __construct(ItemModel $model, ItemValidator $validator)
    {
        parent::__construct($model, $validator);
    }

    public function setWastage(WastageModel $wastage)
    {
        $this->wastage = $wastage;
        $this->setDepartment($wastage->department);
    }

    public function setDepartment(DepartmentModel $department)
    {
        $this->department = $department;
    }

    public function setProduct(ProductModel $product)
    {
        $this->product = $product;
    }

    /**
     * Sync item data for a new wastage.
     *
     * @param  \REDACTED\Wastage\WastageModel            $wastage 
     * @param  \Illuminate\Database\Eloquent\Collection  $products
     * @param  array                                     $items    Item input
     *
     * @return array  Array of WastageItem models
     */
    public function syncNewWastageItems(WastageModel $wastage, Collection $products, array $items)
    {
        $this->setWastage($wastage);

        $itemModels = [];

        foreach ($items as $key => $value) {
            $data = $this->buildItemData($key, $value, $products);
            $item = $this->create($data);

            $itemModels[] = $item;
        }

        return $itemModels;
    }

    /**
     * Sync items for an existing wastage. Updates the item entry if it exists,
     * or creates them if they don't.
     *
     * @param  \REDACTED\Wastage\WastageModel            $wastage
     * @param  \Illuminate\Database\Eloquent\Collection  $products
     * @param  array                                     $items    Item input
     *
     * @return array  Array of WastageItem models
     */
    public function syncExistingWastageItems(WastageModel $wastage, Collection $products, array $items)
    {
        $itemModels = [];

        foreach ($items as $key => $value) {
            if ($item = $this->findProductItem($wastage->items, $key)) {
                $this->checkIntegrity($item);
                $item->update($value);
            } else {
                $data = $this->buildItemData($key, $value, $products);
                $item = $this->create($data);
            }

            $itemModels[] = $item;
        }

        return $itemModels;
    }

    /**
     * Find the item entry of a specific product key in a collection of items.
     *
     * @param  \Illuminate\Database\Eloquent\Collection $items
     * @param  mixed                                    $key   The product's primary key.
     *
     * @return \REDACTED\Wastage\ItemModel|null
     */
    protected function findProductItem(Collection $items, $key)
    {
        if ($key instanceof ItemModel) {
            $key = $key->getKey();
        }

        return $items->first(function($id, $item) use($key) {
            return $item->product_id == $key;
        });
    }

    /**
     * Check the integrity of an item model. Add missing data if necessary.
     *
     * @param  \REDACTED\Wastage\ItemModel $item
     *
     * @return void
     */
    protected function checkIntegrity(ItemModel $item)
    {
        if ($item->department_id < 1) {
            $item->department()->associate($this->department);
        }

        if ($item->wastage_id < 1) {
            $item->wastage()->associate($this->wastage);
        }
    }

    /**
     * Build the pivot table data for a particular item.
     *
     * @param  mixed                                    $key      The product's primary key.
     * @param  array                                    $data
     * @param  \Illuminate\Database\Eloquent\Collection $products
     *
     * @return array
     */
    protected function buildItemData($key, array $data, Collection $products)
    {
        if (!$product = $products->find($key)) {
            throw new \RuntimeException("ID $key not found in products collection");
        }

        $this->setProduct($product);

        if (!empty($data['purchased_amount'])) {
            $data['purchased_for'] = $product->in_price;
        }

        if (!empty($data['sold_amount'])) {
            $data['sold_for'] = $product->out_price;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeCreate($item, array $attributes)
    {
        if ($this->wastage) {
            $item->wastage()->associate($this->wastage);
        }

        if ($this->department) {
            $item->department()->associate($this->department);
        }

        if ($this->product) {
            $item->product()->associate($this->product);
            $this->product = null;
        }
    }
}
