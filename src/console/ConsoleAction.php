<?php
namespace mirocow\elasticsearch\console;

use mirocow\elasticsearch\exceptions\SearchIndexerException;
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
     * @param SearchIndexerException $e
     * @return void
     */
    protected function stdDebug(SearchIndexerException $e)
    {
        $isDebug = $this->controller->debug? $this->controller->debug: \Yii::$app->getModule(Module::MODULE_NAME)->isDebug;

        if($isDebug) {
            $message = $e->getMessage();
            if($previous = $e->getPrevious()) {
                $message .= PHP_EOL . $previous->getMessage();
            }

            $this->getController()->stderr('Error:', Console::FG_RED);
            $this->getController()->stderr(' ' . $e->getFile() . ' (' . $e->getLine() . ')' . PHP_EOL);
            $this->getController()->stderr(' ' . $message . PHP_EOL);

            $this->getController()->stderr('Trace:', Console::FG_GREY);
            $this->getController()->stderr(PHP_EOL . $e->getTraceAsString() . PHP_EOL);


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
