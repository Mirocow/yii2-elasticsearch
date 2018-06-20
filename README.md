# Elasticsearch module based on official Elasticsearch PHP library

[![Join the chat at https://gitter.im/Mirocow/yii2-elasticsearch](https://badges.gitter.im/Mirocow/yii2-elasticsearch.svg)](https://gitter.im/Mirocow/yii2-elasticsearch?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

Docs are available in english and [russian](README.ru.md).

# Install

```bash
$ composer require --prefer-dist mirocow/yii2-elasticsearch
```

# Configure

* Create a class that implements the `common\modules\elasticsearch\contracts\Index` interface.
* Add it to the module configuration in `common/config/main.php`
* Start indexing

```php
return [
    'modules' => [

        // elasticsearch
        common\modules\elasticsearch\Module::MODULE_NAME => [
          'class' => common\modules\elasticsearch\Module::class,
          'indexes' => [
            common\repositories\indexes\ProductsSearchIndex::class
          ]
        ],

    ],
    'bootstrap' => [
        common\modules\elasticsearch\Bootstrap::class
    ]
];
```

# Create index

Create empty index
```bash
$ php yii elasticsearch/index/create index_name
```

Fill index with all documents
```bash
$ php yii elasticsearch/index/populate index_name
```

Destroy an index and all its data
```bash
$ php yii elasticsearch/index/destroy index_name
```

Remove all existing indexes, re-create all indexes and re-index all documents for all indexes
```bash
$ php yii elasticsearch/index/rebuild
```

# Debug

```bash
$ export PHP_IDE_CONFIG="serverName=www.site.loc" && export XDEBUG_CONFIG="remote_host=192.168.1.6 idekey=xdebug" && php7.0 ./yii elasticsearch/index/create products_search
```

# Query

For creating queries, you may use https://github.com/crowdskout/es-search-builder
