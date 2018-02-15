<?php
namespace common\modules\elasticsearch\components\loggers;

use common\modules\elasticsearch\contracts\ProgressLogger;
use Yii;

final class YiiProgressLogger implements ProgressLogger
{
    /** @inheritdoc */
    public function logMessage($message)
    {
        Yii::info($message, 'elasticsearch');
    }

    /** @inheritdoc */
    public function logProgress($totalSteps, $currentStep)
    {
    }
}
