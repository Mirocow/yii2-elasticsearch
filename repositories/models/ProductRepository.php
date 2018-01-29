<?php
namespace common\repositories\models;

use common\models\essence\Product;
use common\repositories\exceptions\EntityNotFoundException;
use yii\db\Query;

final class ProductRepository implements \common\modules\elasticsearch\contracts\Repository
{
    /** @var string */
    private $tableName;

    /**
     * ARProductRepository constructor.
     */
    public function __construct()
    {
        $this->tableName = Product::tableName();
    }

    /** @inheritdoc */
    public function get(int $id)
    {
        /** @var Product|null $product */
        $product = Product::find()
                          ->where([
                            'id' => $id,
                            'product_status_id' => 1
                          ])
                          ->one();
        if (!$product) {
            throw new EntityNotFoundException('Product with id '.$id.' not found');
        }
        return $product;
    }

    /** @inheritdoc */
    public function ids()
    {
        $query = (new Query())
          ->select(['id'])
          ->from($this->tableName)
          ->where(['product_status_id' => 1]);

        foreach ($query->each() as $row) {
            yield (int) $row['id'];
        }
    }

    /** @inheritdoc */
    public function count(): int
    {
        return (int) (new Query())
          ->from($this->tableName)
          ->where(['product_status_id' => 1])
          ->count();
    }
}