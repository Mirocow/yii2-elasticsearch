<?php
namespace common\modules\elasticsearch\contracts;

/**
 * Interface ProgressLogger
 * @package common\modules\elasticsearch\contracts
 */
interface ProgressLogger
{
    /**
     * Добавить сообщение в лог
     *
     * @param string $message
     * @return void
     */
    public function logMessage(string $message) ;

    /**
     * Рассчитать текущий прогресс индексации и добавить его в лог
     *
     * @param int $totalSteps
     * @param int $currentStep
     * @return void
     */
    public function logProgress(int $totalSteps, int $currentStep) ;
}
