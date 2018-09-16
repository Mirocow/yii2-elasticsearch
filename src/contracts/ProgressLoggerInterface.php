<?php
namespace mirocow\elasticsearch\contracts;

/**
 * Interface ProgressLoggerInterface
 * @package mirocow\elasticsearch\contracts
 */
interface ProgressLoggerInterface
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
