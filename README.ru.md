# Установка

```bash
$ composer require --prefer-dist mirocow/yii2-elasticsearch
```

# Настройки

* создать класс реализующий интерфейс common\modules\elasticsearch\contracts\IndexInterface
* добавить его в настройках модуля индексации в common/config/main.php
* запустить индексацию
* Построить запрос используя построители (QueryBuilder - для самого запроса и (AggBuilder и AggregationMulti) для построения агрегации)
* Воспользоваться помошником QueryHelper, для упрощения построения запроса
* Для вывода можно воспользоваться ActiveRecod: ModelPopulate или ActiveProvider: SearchDataProvider

```php
return [
    'modules' => [

        'elasticsearch' => [
            'class' => mirocow\elasticsearch\Module::class,
            'indexes' => [
                // Содержит инструкции для создания индекса продуктов
                common\repositories\indexes\ProductIndex::class => [
                    'class' => common\repositories\indexes\ProductIndex::class,
                    'index_name' => 'es_index_products',
                    'index_type' => 'products',
                ],
            ],
            'isDebug' => true,
        ],

    ],
    'bootstrap' => [
        common\modules\elasticsearch\Bootstrap::class
    ]
];
```

# Построитель индекса

Создать пустой индекс
```bash
$ php yii elasticsearch/index/create index_name
```

Заполнить индекс всеми документами
```bash
$ php yii elasticsearch/index/populate index_name
```

Удалить индекс и все его данные
```bash
$ php yii elasticsearch/index/destroy index_name
```

Удалить все индексы если они существуют, создать все индексы, проиндексировать документы во всех индексах
```bash
$ php yii elasticsearch/index/rebuild
```

# Debug

```bash
$ export PHP_IDE_CONFIG="serverName=www.skringo.ztc" && export XDEBUG_CONFIG="remote_host=192.168.1.6 idekey=xdebug" && php7.0 ./yii elasticsearch/index/create products_search
```

## Пример использования

### QueryHelper: построитель частей запроса

```php
$terms = QueryHelper::terms('categories.name', 'my category');

$nested[] = QueryHelper::nested('string_facet',
    QueryHelper::filter([
        QueryHelper::term('string_facet.facet_name', ['value' => $id, 'boost' => 1]),
        QueryHelper::term('string_facet.facet_value', ['value' => $value, 'boost' => 1]),
    ])
);
$filter[] = QueryHelper::should($nested);

```

### QueryBuilder: построитель запроса

```php
use mirocow\elasticsearch\components\queries\QueryBuilder;

class ProductFacets extends ProductIndex
{

    /**
     * @param ProductSearch $model
     * @return QueryBuilder
     * @throws \Exception
     */
    public function getQueryFascetes(ProductSearch $model)
    {

        $should = [];
        $must = [];
        $must_not = [];
        $filter = [];
        
        $_should[] = QueryHelper::multiMatch([
            'title.ru^10',
            'title.en^10',
            'description.ru^20',
            'description.en^20',
            'model.name^20',
            'brand.name^15',
            'categories.name^10',
        ], $queryCorrect, 'phrase', ['operator' => 'or', 'boost' => 0.5]);
        
        $must[] = QueryHelper::should($_should);
        
        /** @var Aggregation $agg */
        $aggBuilder = new AggBuilder();
        
        $aggregations = new AggregationMulti;
        
        $terms = QueryHelper::terms('categories.name', 'my category');
        $must[] = $terms;
        $agg = $aggBuilder->filter('categories.id', $terms)
            ->add($aggBuilder->terms('categories.id'));
        $aggregations->add('categories', $agg);
        
        $terms = QueryHelper::terms('brand.name', 'my brand');
        $must[] = $terms;
        $agg = $aggBuilder->filter('brand.id', $terms)
            ->add($aggBuilder->terms('brand.id'));
        $aggregations->add('brands', $agg);        
        
        /** @var QueryBuilder $query */
        $query = new QueryBuilder;
        $query = $query
            ->add(QueryHelper::bool($filter, $must, $should, $must_not))
            ->aggregations($aggregations)
            ->withSource('attributes');
            
        return $query;    
    
   }
}   
```

### Model: индексируемая модель

```php
use mirocow\elasticsearch\components\indexes\ModelPopulate;

final class ProductPopulate extends ModelPopulate
{
    public $modelClass = Product::class;
}
```

### SearchDataProvider: вывод с помощью ActiveProvider

```php
use mirocow\elasticsearch\components\queries\QueryBuilder;
use mirocow\elasticsearch\components\factories\IndexerFactory;
use mirocow\elasticsearch\components\indexes\SearchDataProvider;

class ProductSearch extends Product
{
    public function search ($params, $pageSize = 9)
    {
        $this->load($params);
    
        /** @var ProductFacets $search */
        $search = IndexerFactory::createIndex(ProductFacets::class);
        
        /** @var QueryBuilder $query */
        $query = $search->getQueryFascetes($this);
        
        $dataProvider = new SearchDataProvider([
            'modelClass' => (new ProductPopulate())->select('_source.attributes'),
            'search' => $search,
            'query' => $query,
            'sort' => QueryHelper::sortBy(['_score' => SORT_ASC]),
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
    }
}
```

### ModelPopulate: вывод с помощью ActiveRecord

```php
use mirocow\elasticsearch\components\queries\QueryBuilder;
use mirocow\elasticsearch\components\factories\IndexerFactory;

class ProductSearch extends Product
{
    public function search ($params, $pageSize = 9)
    {
        $this->load($params);
        
        /** @var ProductFacets $search */
        $search = IndexerFactory::createIndex(ProductFacets::class);
        
        /** @var QueryBuilder $query */
        $query = $search->getQueryFascetes($this);
        
        $products = (new ProductPopulate())
                    ->select('_source.attributes')
                    ->search($query)
                    ->result();
    }
}
```

# Документация или FAQ

* https://www.elastic.co/guide/en/elasticsearch/reference/5.6/index.html
* https://discuss.elastic.co/c/in-your-native-tongue/russian
* https://ru.stackoverflow.com/search?q=Elasticsearch
* http://qaru.site/search?query=ElasticSearch
* https://discuss.elastic.co/c/elasticsearch

# TODO

Посмотреть на:

* https://github.com/inpsyde/elastic-facets
* https://github.com/ongr-io/ElasticsearchDSL/tree/master/src (https://github.com/ongr-io/ElasticsearchDSL/blob/master/docs/index.md)
* https://github.com/kiwiz/ecl ?
* https://github.com/ruflin/Elastica
* https://hotexamples.com/ru/examples/elastica.aggregation/Terms/-/php-terms-class-examples.html
