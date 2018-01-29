<?php
namespace common\repositories\indexes;

use common\models\essence\Cat;
use common\models\essence\Product;
use common\models\essence\ProductProp;
use common\models\essence\Properties;
use common\models\essence\PropSelectValues;
use common\models\essence\Tag;
use common\models\mm\MmWish;
use common\modules\elasticsearch\components\indexes\AbstractSearchIndex;
use common\modules\elasticsearch\exceptions\SearchIndexerException;
use common\repositories\exceptions\EntityNotFoundException;
use common\repositories\models\ProductRepository;

/**
 * Class ProductsSearchIndex
 * @package common\repositories\indexes
 */
class ProductsSearchIndex extends AbstractSearchIndex
{
    /** @var string */
    const INDEX_NAME = 'es_index_products';

    /** @var string */
    const INDEX_TYPE = 'es_index_products';

    /** @var ProductRepository */
    private $products;

    /**
     * ProductsSearchIndex constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->products = new ProductRepository();
    }

    /** @inheritdoc */
    public static function name()
    {
        return self::INDEX_NAME;
    }

    /** @inheritdoc */
    public static function type()
    {
        return self::INDEX_TYPE;
    }

    /** @inheritdoc */
    public static function accepts($document)
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

    /**
     * @inheritdoc
     * @param Product $document
     */
    public function add($document)
    {
        $this->indexProduct($document);
    }

    /** @inheritdoc */
    public function addById(int $documentId)
    {
        try {
            $product = $this->products->get($documentId);
        } catch (EntityNotFoundException $e) {
            throw new SearchIndexerException('Product with id '.$documentId.' does not exist', 0, $e);
        }

        $this->indexProduct($product);
    }

    /**
     * @inheritdoc
     * @param Product $document
     */
    public function remove($document)
    {
        $this->removeProduct($document);
    }

    /** @inheritdoc */
    protected function indexConfig() :array
    {
        return [
            'index' => self::name(),
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'analysis' => [
                        'analyzer' => [
                            // victoria's, victorias, victoria
                            'autocomplete' => [
                                'type' => 'custom',
                                'tokenizer' => 'standard',
                                "filter" => [
                                    // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-standard-tokenfilter.html
                                    "standard",
                                    // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-lowercase-tokenizer.html
                                    "lowercase",
                                    // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-stop-tokenfilter.html
                                    "stop",
                                    // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-asciifolding-tokenfilter.html
                                    "asciifolding",
                                    // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-porterstem-tokenfilter.html
                                    "porter_stem",
                                    //"english_stemmer",
                                    //"russian_stemmer",
                                    "_delimiter",
                                ],
                            ],
                            'search_analyzer' => [
                                'type' => 'custom',
                                'tokenizer' => 'standard',
                                "filter" => [
                                    // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-standard-tokenfilter.html
                                    "standard",
                                    // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-lowercase-tokenizer.html
                                    "lowercase",
                                    // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-stop-tokenfilter.html
                                    "stop",
                                    // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-asciifolding-tokenfilter.html
                                    "asciifolding",
                                    // https://www.elastic.co/guide/en/elasticsearch/reference/5.6/analysis-porterstem-tokenfilter.html
                                    "porter_stem",
                                    //"english_stemmer",
                                    //"russian_stemmer",
                                ],
                            ],
                        ],
                        'filter' => [
                            '_delimiter' => [
                                "type" => "word_delimiter",
                                "generate_word_parts" => true,
                                "catenate_words" => true,
                                "catenate_numbers" => true,
                                "catenate_all" => true,
                                "split_on_case_change" => true,
                                "preserve_original" => true,
                                "split_on_numerics" => true,
                                "stem_english_possessive" => true // `s
                            ],
                            /*"english_stemmer" => [
                                "type" => "stemmer",
                                "language" => "english",
                            ],
                            "russian_stemmer" => [
                                "type" => "stemmer",
                                "language" => "russian",
                            ],*/
                        ],
                    ],
                ],
                'mappings' => [
                    self::name() => [
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                            ],
                            'categories' => [
                                "properties" => [
                                    "id" => [
                                        'type' => 'integer',
                                    ],
                                    'parent_id' => [
                                        'type' => 'integer',
                                    ],
                                    'urlname' => [
                                        'type' => 'text',
                                        'index' => 'not_analyzed',
                                    ],
                                    "name" => [
                                        'type' => 'text',
                                        'boost' => 2.0,
                                        'index' => 'analyzed',
                                        'analyzer' => 'autocomplete',
                                        'search_analyzer' => 'search_analyzer',
                                        //'store' => 'yes',
                                        'fielddata' => true, // Для aggregations terms
                                    ],
                                ],
                            ],
                            'props' => [
                                "properties" => [
                                    "id" => [
                                        'type' => 'integer',
                                    ],
                                    "prop_id" => [
                                        'type' => 'integer',
                                    ],
                                    "name" => [
                                        'type' => 'text',
                                        'index' => 'not_analyzed',
                                    ],
                                    "value" => [
                                        'type' => 'text',
                                        'boost' => 2.0,
                                        'index' => 'analyzed',
                                        'analyzer' => 'autocomplete',
                                        'search_analyzer' => 'search_analyzer',
                                        //'store' => 'yes',
                                        'fielddata' => true, // Для aggregations terms
                                    ],
                                ],
                            ],
                            'model' => [
                                "properties" => [
                                    'id' => [
                                        'type' => 'integer',
                                    ],
                                    "name" => [
                                        'type' => 'text',
                                        'boost' => 2.0,
                                        'index' => 'analyzed',
                                        'analyzer' => 'autocomplete',
                                        'search_analyzer' => 'search_analyzer',
                                        //'store' => 'yes',
                                        'fielddata' => true, // Для aggregations terms
                                    ],
                                    'parent_id' => [
                                        'type' => 'integer',
                                    ],
                                ],
                            ],
                            'category' => [
                                "properties" => [
                                    "id" => [
                                        'type' => 'integer',
                                    ],
                                    'parent_id' => [
                                        'type' => 'integer',
                                    ],
                                    'urlname' => [
                                        'type' => 'text',
                                        'index' => 'not_analyzed',
                                    ],
                                    "name" => [
                                        'type' => 'text',
                                        'boost' => 2.0,
                                        'index' => 'analyzed',
                                        'analyzer' => 'autocomplete',
                                        'search_analyzer' => 'search_analyzer',
                                        //'store' => 'yes',
                                        'fielddata' => true, // Для aggregations terms
                                    ],
                                ],
                            ],
                            'brand' => [
                                "properties" => [
                                    'id' => [
                                        'type' => 'integer',
                                    ],
                                    "name" => [
                                        'type' => 'text',
                                        'boost' => 2.0,
                                        'index' => 'analyzed',
                                        'analyzer' => 'autocomplete',
                                        'search_analyzer' => 'search_analyzer',
                                        //'store' => 'yes',
                                        'fielddata' => true, // Для aggregations terms
                                    ],
                                    "aliases" => [
                                        'type' => 'text',
                                        'boost' => 3.0,
                                        'index' => 'analyzed',
                                        'analyzer' => 'autocomplete',
                                        'search_analyzer' => 'search_analyzer',
                                        'store' => 'yes',
                                    ],
                                ],
                            ],
                            'title' => [
                                "properties" => [
                                    'en' => [
                                        'type' => 'text',
                                        'index' => 'analyzed',
                                        'analyzer' => 'english',
                                    ],
                                    'ru' => [
                                        'type' => 'text',
                                        'index' => 'analyzed',
                                        'analyzer' => 'russian',
                                    ],
                                ]
                            ],
                            'description' => [
                                "properties" => [
                                    'en' => [
                                        'type' => 'text',
                                        'index' => 'analyzed',
                                        'analyzer' => 'english',
                                    ],
                                    'ru' => [
                                        'type' => 'text',
                                        'index' => 'analyzed',
                                        'analyzer' => 'russian',
                                    ],
                                ]
                            ],
                            'product_status_id' => [
                                'type' => 'integer',
                            ],
                            'user_id' => [
                                'type' => 'integer',
                            ],
                            'product_wish' => [
                                'type' => 'integer',
                                'store' => 'yes',
                            ],
                            'created_at' => [
                                "type" => "date",
                                // 2016-12-28 16:21:30
                                "format" => "yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis",
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param Product $product
     * @throws SearchIndexerException
     */
    private function indexProduct(Product $product)
    {
        /**
         * Categories
         */

        $categories = [];
        /** @var Cat $cat */
        foreach ($product->cat->getCatAndParents() as $cat){
            $categories[] = [
              'id' => $cat->id,
              'parent_id' => $cat->parent_id,
              'name' => $cat->name,
              'urlname' => $cat->urlname,
            ];
        }

        /**
         * Properties
         */

        $props = [];
        $properties = ProductProp::find()->where(['product_id' => $product->id])->joinWith('props')->all();
        /** @var Properties $property */
        foreach ($properties as $property){
            $value = PropSelectValues::find()->where(['id' => $property->prop_value, 'prop_id' => $property->prop_id])->one();
            if($value) {
                $props[] = [
                    'id'    => $property->id,
                    'prop_id' => $value->id,
                    'name'  => $property->props->prop_descr,
                    'value' => $value->prop_value,
                ];
            }
        }

        /**
         * Brands
         */

        $brand_aliases = [];
        /** @var Tag $alias */
        foreach ($product->brand->getBrandAliases() as $alias){
            if($aliasName = $alias->name) {
                $brand_aliases[] = $aliasName;
            }
        }

        /**
         * Wish
         */
        $product_wish = [];
        /** @var MmWish $wish */
        foreach ($product->getWish() as $wish){
            $product_wish[] = $wish->user_id;
        }

        $productName = $product->getName();

        $body = [
            'id' => $product->id,
            'title' => [
                'ru' => $productName,
                'en' => $productName,
            ],
            'description' => [
                'ru' => $product->description,
                'en' => $product->description,
            ],
            'product_status_id' => $product->product_status_id,
            'user_id' => $product->user_id,
            'created_at' => $product->created_at,
        ];

        if($product->cat_id){
            $body['category'] = [
                'id' => $product->cat_id,
                'parent_id' => $product->cat->parent_id,
                'name' => $product->cat->name,
                'urlname' => $product->cat->urlname,
            ];
        }

        if($product->product_model){
            $body['model'] = [
                'id' => $product->product_model,
                'name' => $product->model->name,
                'parent_id' => $product->model->parent_id,
            ];
        }

        if($product->brand_id){
            $body['brand'] = [
                'id' => $product->brand_id,
                'name' => $product->brand->name,
            ];

            if($brand_aliases){
                $body['brand']['aliases'] = $brand_aliases;
            }
        }

        if($props){
            $body['props'] = $props;
        }

        if($categories){
            $body['categories'] = $categories;
        }

        if($product_wish){
            $body['product_wish'] = $product_wish;
        }

        $this->client->index(
            [
                'index' => self::name(),
                'type' => self::type(),
                'id' => $product->id,
                'body' => $body,
            ]
        );
    }

    /**
     * @param Product $product
     * @throws SearchIndexerException
     */
    private function removeProduct(Product $product)
    {
        $this->deleteInternal($product->id);
    }

}
