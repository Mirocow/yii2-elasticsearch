<?php
namespace mirocow\elasticsearch\console;

use mirocow\elasticsearch\Module;
use yii\base\Action;
use yii\base\Event;
use yii\console\Controller;
use yii\helpers\Console;
use Yii;

class ConsoleAction extends Action
{
    public function init()
    {
        if(Yii::$app->has('mutex')) {
            Event::on(Controller::className(), Controller::EVENT_BEFORE_ACTION, function ($event)
            {
                if (!$this->acquireMutex()) {
                    $this->getController()->stderr("This process is already running.\n\n", Console::FG_RED);
                    exit;
                }
            });

            Event::on(Controller::className(), Controller::EVENT_AFTER_ACTION, function ($event)
            {
                $this->releaseMutex();
            });
        }

        parent::init();
    }

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

    /**
     * @return \yii\mutex\Mutex mutex component
     */
    protected function getMutex()
    {
        return Yii::$app->get('mutex');
    }

    /**
     * Acquires current action lock.
     * @return bool lock acquiring result.
     */
    protected function acquireMutex()
    {
        return $this->getMutex()->acquire($this->composeMutexName());
    }

    /**
     * Release current action lock.
     * @return bool lock release result.
     */
    protected function releaseMutex()
    {
        return $this->getMutex()->release($this->composeMutexName());
    }

    /**
     * Composes the mutex name.
     * @return string mutex name.
     */
    protected function composeMutexName()
    {
        return self::class;
    }

}
