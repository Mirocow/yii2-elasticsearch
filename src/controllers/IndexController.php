<?php
namespace mirocow\elasticsearch\controllers;

use mirocow\elasticsearch\contracts\ProgressLoggerInterface;
use mirocow\elasticsearch\controllers\index\actions\ActionCreate;
use mirocow\elasticsearch\controllers\index\actions\ActionDestroy;
use mirocow\elasticsearch\controllers\index\actions\ActionPopulate;
use mirocow\elasticsearch\controllers\index\actions\ActionRebuild;
use mirocow\elasticsearch\controllers\index\actions\ActionUpgrade;
use yii\console\Controller;

class IndexController extends Controller
{
    public $interactive;

    public $skipExists = false;

    public $skipNotExists = false;

    public $debug = false;

    public function options($actionID)
    {
        // $actionId might be used in subclasses to provide options specific to action id
        return [
            'interactive',
            'skipExists',
            'skipNotExists',
            'debug',
        ];
    }

    public function beforeAction($action)
    {
        /** @var ProgressLoggerInterface $logger */
        $logger = \Yii::$container->get(ProgressLoggerInterface::class);

        $logger->interactive = $this->interactive;

        foreach ($this->options($action->id) as $option){
            if(isset($action->{$option})){
                $action->{$option} = $this->{$option};
            }
        }

        return parent::beforeAction($action);
    }

    /**
     * @return array
     */
    public function actions() :array
    {
        return [
            'create' => ActionCreate::class,
            'populate' => ActionPopulate::class,
            'index' => ActionPopulate::class,
            'destroy' => ActionDestroy::class,
            'rebuild' => ActionRebuild::class,
            'reindex' => ActionRebuild::class,
            'upgrade' => ActionUpgrade::class,
            'update' => ActionUpgrade::class
        ];
    }
}
