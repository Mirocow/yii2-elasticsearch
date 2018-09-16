<?php
namespace common\modules\elasticsearch\components\loggers;

use common\modules\elasticsearch\contracts\ProgressLoggerInterface;
use Yii;

final class YiiProgressLogger implements ProgressLoggerInterface
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
