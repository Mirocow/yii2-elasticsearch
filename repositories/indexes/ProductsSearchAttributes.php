<?php
namespace common\repositories\indexes;

use common\helpers\BaseHelper;
use common\models\essence\Cat;
use common\models\essence\Product;
use common\models\essence\ProductProp;
use common\models\essence\Properties;
use common\models\essence\PropSelectValues;
use common\modules\elasticsearch\components\indexes\AbstractSearchIndex;
use common\modules\elasticsearch\components\queries\AggBuilder;
use common\modules\elasticsearch\components\queries\helpers\QueryHelper;
use common\modules\elasticsearch\components\queries\QueryBuilder;
use common\modules\elasticsearch\exceptions\SearchIndexerException;
use common\repositories\exceptions\EntityNotFoundException;
use common\repositories\models\CategoryPopulate;
use common\repositories\models\ProductPopulate;
use common\repositories\models\ProductRepository;
use frontend\models\searchmodels\ProductSearch;
use yii\helpers\ArrayHelper;

/**
 * Class ProductsSearchAttributes
 * @package common\repositories\indexes
 */
class ProductsSearchAttributes extends ProductsSearchIndex
{
    public $withSource = '*';

    /**
     * @param string $q
     * @return array|mixed
     * @throws \Exception
     */
    public function searchBrand(string $q)
    {
        $q = mb_strtolower(trim($q));
        $should = [];
        $should[] = QueryHelper::match('brand.name', $q);
        $should[] = QueryHelper::match('brand.aliases', $q);
        $queryCorrect = BaseHelper::correctString($q);
        $should[] = QueryHelper::match('brand.name', $queryCorrect);
        $should[] = QueryHelper::match('brand.aliases', $queryCorrect);
        $query = QueryHelper::should($should);
        $result = $this
            ->query($query)
            ->limit(1)
            ->searchDocuments();

        if(empty($result['hits']['hits'])){
            return [];
        }

        $model = (new CategoryPopulate($result))->asArray()->one();

        return !empty($model['brand'])? $model['brand']['name']: '';
    }

    /**
     * @param string $q
     * @return array
     * @throws \Exception
     */
    public function searchCategoriesByName(string $q) :array
    {
        $q = mb_strtolower(trim($q));
        $should = [];
        $must = [];
        $should[] = QueryHelper::match('title.en', $q);
        $should[] = QueryHelper::match('title.ru', $q);

        if ($brand_name = $this->searchBrand($q)) {
            $must[] = QueryHelper::match('brand.name', $brand_name);
        } else {
            $should[] = QueryHelper::match('brand.name', $q);
        }

        if(isset($catId)){
            $should[] = QueryHelper::match('categories.id', $catId);
        } else {
            $should[] = QueryHelper::match('categories.name', $q);
        }
        $query = QueryHelper::should($should);

        if($must) {
            $query = ArrayHelper::merge($query, QueryHelper::must($must));
        }

        $aggBuilder = new AggBuilder;
        $agg = $aggBuilder->terms('categories.id', ['order' => ['_term' => 'asc']], $aggBuilder->topHits(['size' => 1]));

        $response = $this
            ->query($query)
            ->aggregations($agg->generateQuery())
            ->limit(5)
            ->searchDocuments();

        $result = $agg->generateResults($response['aggregations']);

        if(empty($result['aggs'])){
            return [];
        }

        return (new CategoryPopulate($result['aggs']))->asArray()->all();

    }

}