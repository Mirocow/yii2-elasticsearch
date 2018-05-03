# Install

```bash
$ composer require --prefer-dist elasticsearch/elasticsearch
```

# Configure

* создать класс реализующий интерфейс mirocow\elasticsearch\contracts\Index
* добавить его в настройках модуля индексации в common/config/main.php
* запустить индексацию

```php
return [
    'modules' => [

        // elasticsearch
        mirocow\elasticsearch\Module::MODULE_NAME => [
          'class' => mirocow\elasticsearch\Module::class,
          'indexes' => [
            common\repositories\indexes\ProductsSearchIndex::class
          ]
        ],

    ],
    'bootstrap' => [
        mirocow\elasticsearch\Bootstrap::class
    ]
];
```

# Create index

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
$ php yii elasticsearch/index/rebuild index_name
```

# Debug

```bash
$ export PHP_IDE_CONFIG="serverName=www.skringo.ztc" && export XDEBUG_CONFIG="remote_host=192.168.1.6 idekey=xdebug" && php7.0 ./yii elasticsearch/index/create products_search
```

# Aggregation

За основу построителя запроса взят https://github.com/crowdskout/es-search-builder.git

# TODO

Посмотреть на:

* https://github.com/inpsyde/elastic-facets
* https://github.com/ongr-io/ElasticsearchDSL/tree/master/src (https://github.com/ongr-io/ElasticsearchDSL/blob/master/docs/index.md)
* https://github.com/kiwiz/ecl ?
* https://github.com/ruflin/Elastica
* https://hotexamples.com/ru/examples/elastica.aggregation/Terms/-/php-terms-class-examples.html
