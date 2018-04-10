<?php
namespace mirocow\elasticsearch\components\loggers;

use mirocow\elasticsearch\contracts\ProgressLogger;
use yii\helpers\Console;

final class ConsoleProgressLogger implements ProgressLogger
{
    public $interactive = false;

    /** @inheritdoc */
    public function logMessage(string $message)
    {
        if($this->interactive){
            return;
        }

        Console::stdout($message.PHP_EOL);
    }

    /** @inheritdoc */
    public function logProgress(int $totalSteps, int $currentStep)
    {
        if($this->interactive){
            return;
        }

        $percent = ceil($currentStep/($totalSteps / 100));
        $lineTerminator = $currentStep < $totalSteps ? '' : PHP_EOL;

        Console::stdout(
            sprintf("\rDone %d", $percent).'%'.
            ' ('.$currentStep.'/'.$totalSteps.')'.
            $lineTerminator
        );

        if ($currentStep < $totalSteps) {
            Console::clearLineAfterCursor();
        }
    }
}
