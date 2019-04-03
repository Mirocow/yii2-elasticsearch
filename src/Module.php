<?php
namespace mirocow\elasticsearch;

class Module extends \yii\base\Module
{
    const VERSION = '1.0.13';

    const MODULE_NAME = 'elasticsearch';

    /** @var string[] */
    public $indexes;

    /** @var bool  */
    public $isDebug = false;

}
