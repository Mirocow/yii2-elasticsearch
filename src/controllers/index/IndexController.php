<?php
namespace common\modules\elasticsearch\controllers\index;

use common\modules\elasticsearch\contracts\ProgressLogger;
use common\modules\elasticsearch\controllers\index\actions\ActionCreate;
use common\modules\elasticsearch\controllers\index\actions\ActionDestroy;
use common\modules\elasticsearch\controllers\index\actions\ActionPopulate;
use common\modules\elasticsearch\controllers\index\actions\ActionRebuild;
use common\modules\elasticsearch\controllers\index\actions\ActionUpgrade;
use yii\console\Controller;

class IndexController extends Controller
{
    public $interactive;

    public function options($actionID)
    {
        // $actionId might be used in subclasses to provide options specific to action id
        return ['interactive'];
    }

    public function beforeAction($action)
    {
        /** @var ProgressLogger $logger */
        $logger = \Yii::$container->get(ProgressLogger::class);

        $logger->interactive = $this->interactive;

        return parent::beforeAction($action);
    }

    /**
     * php ./yii elasticsearch/index/create es_index_products
     * php ./yii elasticsearch/index/populate es_index_products
     * php ./yii elasticsearch/index/upgrade es_index_products
     * php ./yii elasticsearch/index/destroy es_index_products
     * @return array
     */
    public function actions() :array
    {
        return [
            'create' => ActionCreate::class,
            'populate' => ActionPopulate::class,
            'destroy' => ActionDestroy::class,
            'rebuild' => ActionRebuild::class,
            'upgrade' => ActionUpgrade::class
        ];
    }
}
