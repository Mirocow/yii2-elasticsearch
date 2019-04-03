<?php
namespace mirocow\elasticsearch\contracts;

use mirocow\elasticsearch\exceptions\SearchIndexerException;

/**
 * Interface IndexInterface
 * @package mirocow\elasticsearch\contracts
 */
interface IndexInterface
{
    /**
     * Возвращает название индекса
     *
     * @return string
     */
    public function name();

    /**
     * Возвращает тип индекса
     *
     * @return string
     */
    public function type();

    /**
     * Определяет может ли иднекс индексировать этот документ
     *
     * @param mixed $document
     * @return bool
     */
    public function accepts($document);

    /**
     * Возвращает идентификаторы всех документов, которые должны быть проиндексированы
     *
     * @return iterable
     */
    public function documentIds();

    /**
     * Возвращает количество всех документов, которые должны быть проиндексированы
     *
     * @return int
     */
    public function documentCount();

    /**
     * Определяет инициализирован индекс или нет
     *
     * @return bool
     */
    public function exists();

    /**
     * Инициализирует индекс
     *
     * @return void
     * @throws SearchIndexerException
     * @return void;
     */
    public function create();

    /**
     * Удаляет индекс
     *
     * @return void
     * @throws SearchIndexerException
     * @return void;
     */
    public function destroy();

    /**
     * Пересчитывает маппинг индекса
     *
     * @return void
     * @throws SearchIndexerException
     * @return void;
     */
    public function upgrade();

    /**
     * Метод добавляет модель в индекс по ID
     *
     * @param int $documentId
     *
     * @return mixed
     */
    public function addDocumentById(int $documentId);
}
