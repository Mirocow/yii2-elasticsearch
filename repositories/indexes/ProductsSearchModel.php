<?php
namespace common\repositories\indexes;

use common\helpers\BaseHelper;
use common\modules\elasticsearch\components\queries\AggBuilder;
use common\modules\elasticsearch\components\queries\helpers\QueryHelper;
use common\repositories\models\ProductPopulate;
use frontend\models\searchmodels\ProductSearch;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * Class ProductsSearchModel
 * @package common\repositories\indexes
 */
class ProductsSearchModel extends ProductsSearchIndex
{
    public $withSource = false;

    /**
     * @param string $q
     * @param int|null $catId
     * @param null $brandId
     * @return array
     * @throws \Exception
     */
    public function searchProductsByName(string $q, int $catId = null, $brandId = null) :array
    {
        $q = mb_strtolower(trim($q));
        $should = [];
        $must = [];
        $should[] = QueryHelper::match('title.en', $q);
        $should[] = QueryHelper::match('title.ru', $q);

        if ($brand_name = (new ProductsSearchAttributes)->searchBrand($q)) {
            $must[] = QueryHelper::match('brand.name', $brand_name);
        } else {
            $should[] = QueryHelper::match('brand.name', $q);
        }

        if($catId){
            $must[] = QueryHelper::match('categories.id', $catId);
        } else {
            $should[] = QueryHelper::match('categories.name', $q);
        }
        $query = QueryHelper::should($should);

        if($must) {
            $query = ArrayHelper::merge($query, QueryHelper::must($must));
        }

        $result = $this
            ->query($query)
            ->limit(5)
            ->searchDocuments();

        if(empty($result['hits']['hits'])){
            return [];
        }

        return (new ProductPopulate($result))->refresh(false)->all();
    }

    public function searchFascetes(ProductSearch $model) :array
    {
        $must = [];
        $filter = [];

        // Свойства
        if($model->Props){
            $properties = [];
            foreach ($model->Props as $id => $vals) {
                if (empty($vals))
                    continue;
                $properties = [];
                foreach ($vals as $value) {
                    $properties[] = $value;
                }
                $must[] = QueryHelper::terms('props.prop_id', $properties);
            }
        }

        // Категория
        if (empty($model->searchtext) && $model->cat_id) {
            $cat = \common\models\essence\Cat::findOne($model->cat_id);
            $must[] = QueryHelper::terms('categories.id', $cat->getCatAndChilds());
        }

        // Категории
        if($model->cats){
            $cats = [];
            if(is_array($model->cats)){
                $cats = $model->cats;
            } else {
                $cats[] = $model->cats;
            }
            $must[] = QueryHelper::terms('categories.id', $cats);
        }

        // Модель
        if($model->model_id){
            $models = [];
            if(is_array($model->model_id)){
                $models = $model->model_id;
            } else {
                $models[] = $model->model_id;
            }
            $must[] = QueryHelper::terms('model.id', $models);
        }

        // Бренд
        if($model->brand_id){
            $brands = [];
            if(is_array($model->brand_id)){
                $brands = $model->brand_id;
            } else {
                $brands[] = $model->brand_id;
            }
            $must[] = QueryHelper::terms('brand.id', $brands);
        }

        // Поисковый запрос
        if ($model->searchtext) {
            $searchtext = BaseHelper::correctString($model->searchtext);
            $should = [];
            $should[] = QueryHelper::query_string($model->searchtext);
            if($searchtext <> $model->searchtext) {
                $should[] = QueryHelper::query_string($searchtext);
            }
            $must[] = QueryHelper::should($should);
        }

        // Продукт
        if($model->user_id) {
            $filter[] = QueryHelper::term('user_id', $model->user_id);
        }
        $filter[] = QueryHelper::term('product_status_id', 1);

        $sort = [];

        if($model->sort){

            switch ($model->direction){
                case 'desc':
                    $direction = SORT_DESC;
                    break;
                case 'asc':
                default:
                    $direction = SORT_ASC;
                    break;
            }

            switch ($model->sort){
                case 'created_at':
                    $sort = QueryHelper::sortBy(['created_at' => $direction]);
                    break;
                case 'popular':
                    $must[] = QueryHelper::queryByScript("doc['product_wish'].values.size() > 0");
                    $sort[] = QueryHelper::sortByCount('product_wish', $direction);
                    break;
            }
        }

        $query = ArrayHelper::merge(
            QueryHelper::must($must),
            QueryHelper::filter($filter)
        );

        $aggs = [];

        $aggs['categories.id'] = [
            'filter' => QueryHelper::query(),
            'aggs' => [
                'categories.id' => [
                    'terms' => [
                        'field' => 'categories.id'
                    ],
                ]
            ],
        ];

        $aggs['brand.id'] = [
            'filter' => QueryHelper::query(),
            'aggs' => [
                'brand.id' => [
                    'terms' => [
                        'field' => 'brand.id'
                    ],
                ]
            ],
        ];

        $aggs['props.id'] = [
            'filter' => QueryHelper::query(),
            'aggs' => [
                'props.id' => [
                    'terms' => [
                        'field' => 'props.id'
                    ],
                ]
            ],
        ];

        $aggs['model.id'] = [
            'filter' => QueryHelper::query(),
            'aggs' => [
                'model.id' => [
                    'terms' => [
                        'field' => 'model.id'
                    ],
                ]
            ],
        ];

        $result = $this
            ->query($query)
            ->aggregations($aggs)
            ->sort($sort)
            ->limit(10000)
            //->store(true)
            ->searchDocuments();

        //$aggregations = $this->result('aggregations');
        //$this->release();

        return (new ProductPopulate($result))->refresh(false)->all();
    }

}