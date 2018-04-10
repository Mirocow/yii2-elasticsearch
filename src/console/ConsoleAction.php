<?php
namespace mirocow\elasticsearch\console;

use mirocow\elasticsearch\Module;
use yii\base\Action;
use yii\console\Controller;
use yii\helpers\Console;

class ConsoleAction extends Action
{
    /**
     * @return Controller
     */
    protected function getController()
    {
        /** @var Controller $this::controller */
        return $this->controller;
    }

    /**
     * @param string $message
     * @return void
     */
    protected function stdErr(string $message)
    {
        $this->getController()->stderr('Error:', Console::FG_RED);
        $this->getController()->stderr(' '.$message.PHP_EOL);
    }

    /**
     * @param string $message
     * @return void
     */
    protected function stdDebug(string $message)
    {
        if(\Yii::$app->getModule(Module::MODULE_NAME)->isDebug) {
            $this->getController()->stderr('Debug:', Console::FG_YELLOW);
            $this->getController()->stderr(' ' . $message . PHP_EOL);
        }
    }
}