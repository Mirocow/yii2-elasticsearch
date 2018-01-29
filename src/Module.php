<?php
namespace common\modules\elasticsearch;

use common\modules\elasticsearch\controllers\index\IndexController;

class Module extends \yii\base\Module
{
    const MODULE_NAME = 'elasticsearch';

    /** @var string[] */
    public $controllerMap = [
        'index' => IndexController::class
    ];

    /** @var string[] */
    public $indexes;

    /** @var bool  */
    public $isDebug = false;

}
