# Elasticsearch module based on official Elasticsearch PHP library

[![http://www.elasticsearch.com](https://github.com/Mirocow/yii2-elasticsearch/blob/master/documents/elasticsearch.jpg)]

[![Latest Stable Version](https://poser.pugx.org/mirocow/yii2-elasticsearch/v/stable)](https://packagist.org/packages/mirocow/yii2-elasticsearch) 
[![Latest Unstable Version](https://poser.pugx.org/mirocow/yii2-elasticsearch/v/unstable)](https://packagist.org/packages/mirocow/yii2-elasticsearch) 
[![Total Downloads](https://poser.pugx.org/mirocow/yii2-elasticsearch/downloads)](https://packagist.org/packages/mirocow/yii2-elasticsearch) [![License](https://poser.pugx.org/mirocow/yii2-elasticsearch/license)](https://packagist.org/packages/mirocow/yii2-elasticsearch)
[![Join the chat at https://gitter.im/Mirocow/yii2-elasticsearch](https://badges.gitter.im/Mirocow/yii2-elasticsearch.svg)](https://gitter.im/Mirocow/yii2-elasticsearch?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2FMirocow%2Fyii2-elasticsearch.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2FMirocow%2Fyii2-elasticsearch?ref=badge_shield)
[![Maintainability](https://api.codeclimate.com/v1/badges/a773029aca32f417b333/maintainability)](https://codeclimate.com/github/Mirocow/yii2-elasticsearch/maintainability)

Docs are available in english and [russian](README.ru.md).

## Honey modules

* [mirocow/yii2-elasticsearch-log](https://github.com/Mirocow/yii2-elasticsearch-log)
* [mirocow/yii2-elasticsearch-debug](https://github.com/Mirocow/yii2-elasticsearch-debug)

## Install 

### Elasticsearch 5.6.x

```bash
$ wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
$ sudo apt-get install apt-transport-https
$ echo "deb https://artifacts.elastic.co/packages/5.x/apt stable main" | sudo tee -a /etc/apt/sources.list.d/elastic-5.x.list
$ sudo apt-get update && sudo apt-get install elasticsearch
$ composer require --prefer-dist mirocow/yii2-elasticsearch
```

## Configure

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

## Create index

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

## Debug

```bash
$ export PHP_IDE_CONFIG="serverName=www.site.loc" && export XDEBUG_CONFIG="remote_host=192.168.1.6 idekey=xdebug" && php7.0 ./yii elasticsearch/index/create products_search
```

## Query

For creating queries, you may use https://github.com/crowdskout/es-search-builder

## License
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2FMirocow%2Fyii2-elasticsearch.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2FMirocow%2Fyii2-elasticsearch?ref=badge_large)

### I use JetBrains products to develop yii2-elasticsearch !
[![www.jetbrains.com](https://github.com/mirocow/yii2-elasticsearch/blob/master/documents/jetbrains.svg)](http://www.jetbrains.com)

