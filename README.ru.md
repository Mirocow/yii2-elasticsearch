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
        mirocow\elasticsearch\Bootstrap::class,
    ]
];
```

## Index repository: хранилище заливаемое в индекс

```php
<?php

namespace common\repositories\repositories;

use common\models\essence\Product;
use common\repositories\exceptions\EntityNotFoundException;

final class ProductRepository implements \mirocow\elasticsearch\contracts\RepositoryInterface
{

    /** @inheritdoc */
    public function get(int $id)
    {
        $product = Product::find()
            ->where(['id' => $id])
            ->one();

        if (!$product) {
            throw new EntityNotFoundException('Product with id ' . $id . ' not found');
        }
        return $product;
    }

    /** @inheritdoc */
    public function ids()
    {
        return Product::find()
            ->alias('product')
            ->select('product.id')
            ->asArray()
            ->each();
    }

    /** @inheritdoc */
    public function count(): int
    {
        return (int)Product::find()
            ->count();
    }
}
```

## Model indexers: для индексации индекса

```php
<?php
namespace common\repositories\indexes;

use common\essence\Product;
use common\repositories\exceptions\EntityNotFoundException;
use common\repositories\repositories\ProductRepository;
use mirocow\elasticsearch\components\indexes\AbstractSearchIndex;
use mirocow\elasticsearch\exceptions\SearchIndexerException;

/**
 * Class ProductIndex
 * @package common\repositories\indexes
 */
class ProductIndex extends AbstractSearchIndex
{
    /** @var string */
    public $index_name = 'index_products';

    /** @var string */
    public $index_type = 'products';

    /** @var ProductRepository */
    private $products;

    /**
     * ProductIndex constructor.
     */
    public function __construct() {
        parent::__construct();

        $this->products = new ProductRepository();
    }

    /** @inheritdoc */
    public function accepts($document)
    {
        return $document instanceof Product;
    }

    /** @inheritdoc */
    public function documentIds()
    {
        return $this->products->ids();
    }

    /** @inheritdoc */
    public function documentCount()
    {
        return $this->products->count();
    }

    /** @inheritdoc */
    public function addById(int $documentId)
    {
        try {
            $document = $this->products->get($documentId);
        } catch (EntityNotFoundException $e) {
            throw new SearchIndexerException('Product with id '.$documentId.' does not exist', 0, $e);
        }
				
        $body = [
            'id' => $product->id,
            'title' => [
                'ru' => $productName,
                'en' => $productName,
            ],
            'attributes' => $product->attributes,
        ];

        if($this->documentExists($document->id)){
            return $this->documentUpdateById($document->id, $body);
        } else {
            return $this->documentCreate($document->id, $body);
        }
				
    }

    /** @inheritdoc */
    protected function indexConfig(): array
    {
      return [
          'index' => $this->name(),
          'body' => [
              'settings' => [
                  'number_of_shards' => 1,
                  'number_of_replicas' => 0,
                  // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-analyzers.html
                  // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis.html
                  'analysis' => [
                      'filter' => [
                          '_delimiter' => [
                              'type' => 'word_delimiter',
                              'generate_word_parts' => true,
                              'catenate_words' => true,
                              'catenate_numbers' => true,
                              'catenate_all' => true,
                              'split_on_case_change' => true,
                              'preserve_original' => true,
                              'split_on_numerics' => true,
                              'stem_english_possessive' => true // `s
                          ],
                          'fulltext_index_ngram_filter' => [
                              'type' => 'edge_ngram',
                              'min_gram' => '2',
                              'max_gram' => '20',
                          ],

                          /**
                           * Russian
                           */

                          "russian_stop" => [
                              "type" => "stop",
                              "stopwords" => "_russian_",
                          ],
                          "russian_keywords" => [
                              "type" => "keyword_marker",
                              "keywords" => ["пример"],
                          ],
                          "russian_stemmer" => [
                              "type" => "stemmer",
                              "language" => "russian",
                          ],

                          /**
                           * English
                           */

                          "english_stop" => [
                              "type" => "stop",
                              "stopwords" => "_english_",
                          ],
                          "english_keywords" => [
                              "type" => "keyword_marker",
                              "keywords" => ["example"],
                          ],
                          "english_stemmer" => [
                              "type" => "stemmer",
                              "language" => "english",
                          ],
                          "english_possessive_stemmer" => [
                              "type" => "stemmer",
                              "language" => "possessive_english",
                          ],
                      ],
                      // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analyzer.html
                      // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/search-analyzer.html
                      'analyzer' => [
                          // victoria's, victorias, victoria
                          'autocomplete' => [
                              'type' => 'custom',
                              'tokenizer' => 'standard',
                              'filter' => [
                                  // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-standard-tokenfilter.html
                                  'standard',
                                  // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-lowercase-tokenizer.html
                                  'lowercase',
                                  // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-stop-tokenfilter.html
                                  'stop',
                                  // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-asciifolding-tokenfilter.html
                                  'asciifolding',
                                  // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-porterstem-tokenfilter.html
                                  'porter_stem',
                                  //'english_stemmer',
                                  //'russian_stemmer',
                                  '_delimiter',
                              ],
                          ],
                          'search_analyzer' => [
                              'type' => 'custom',
                              'tokenizer' => 'standard',
                              'filter' => [
                                  // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-standard-tokenfilter.html
                                  'standard',
                                  // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-lowercase-tokenizer.html
                                  'lowercase',
                                  // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-stop-tokenfilter.html
                                  'stop',
                                  // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-asciifolding-tokenfilter.html
                                  'asciifolding',
                                  // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-porterstem-tokenfilter.html
                                  'porter_stem',
                                  //'english_stemmer',
                                  //'russian_stemmer',
                              ],
                          ],
                          'fulltext_index_analyzer_ru' => [
                              'filter' => [
                                  "lowercase",
                                  "russian_stop",
                                  "russian_keywords",
                                  "russian_stemmer",
                                  "fulltext_index_ngram_filter",
                              ],
                              'tokenizer' => 'standard',
                          ],
                          'fulltext_index_analyzer_en' => [
                              'filter' => [
                                  "english_possessive_stemmer",
                                  "lowercase",
                                  "english_stop",
                                  "english_keywords",
                                  "english_stemmer",
                                  "fulltext_index_ngram_filter",
                              ],
                              'tokenizer' => 'standard',
                          ],
                      ],
                  ],
              ],
              'mappings' => [
                  $this->type() => [
                      // Определяет базовый набор свойств для группы полей
                      'dynamic_templates' => [
                          [
                              'attributes' => [
                                  'path_match' => 'attributes.*',
                                  'mapping' => [
                                      'index' => false,
                                  ],
                              ],
                          ],
                      ],
                      // При индексировании поля _all все поля документа объединяются в одну большую строку независимо от типа данных.
                      // По умолчанию поле _all включено.
                      "_all" => [
                          "enabled" => false
                      ],
                      'properties' => [
                          // Возвращаемые данные, не индексируются
                          // Заполняет модель методом populate
                          // Не индексируется
                          'attributes' => [
                              'properties' => [
                                  'created_at' => [
                                      "type" => "date",
                                      // 2016-12-28 16:21:30
                                      "format" => "yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis",
                                  ],
                              ],
                          ],
                          'title' => [
                              'properties' => [
                                  'en' => [
                                      'type' => 'text',
                                      'search_analyzer' => 'fulltext_index_analyzer_en',
                                      'analyzer' => 'fulltext_index_analyzer_en',
                                      //'analyzer' => 'english',
                                  ],
                                  'ru' => [
                                      'type' => 'text',
                                      'search_analyzer' => 'fulltext_index_analyzer_ru',
                                      'analyzer' => 'fulltext_index_analyzer_ru',
                                      //'analyzer' => 'russian',
                                  ],
                              ],
                          ],
                      ],
                  ],
              ],
          ],
      ];
    }

}
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
## Model

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

## Документация

Отладочный поисковый запрос
Если вы когда-либо задавались вопросом, почему документ не соответствует запросу, сначала проверьте сопоставление типа с помощью API-интерфейса GET, как показано здесь:

```GET example6/product/_mapping```

Если отображение правильное, убедитесь, что значение текстового поля проанализировано правильно. Вы можете использовать API анализа для проверки списка токенов, как показано ниже:

```GET _analyze?analyzer=russian&amp;text=Маша+любит+вареники```

Если анализатор ведет себя так, как ожидалось, убедитесь, что вы используете правильный тип запроса. Например, используя совпадение вместо запроса термина и так далее.

### Анализ и маппинг

* https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-analyzers.html
* https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis.html
* https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analyzer.html

## Ссылки

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
