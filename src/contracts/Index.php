<?php
namespace mirocow\elasticsearch\contracts;

use mirocow\elasticsearch\exceptions\SearchIndexerException;

/**
 * Interface Index
 * @package mirocow\elasticsearch\contracts
 */
interface Index
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
     * Добавляет документ в индекс
     *
     * @param mixed $document
     * @throws SearchIndexerException
     * @return void
     */
    public function add($document) ;

    /**
     * Добавляет документ в индекс по его идентификатору
     *
     * @param int $documentId
     * @throws SearchIndexerException
     * @return void
     */
    public function addById(int $documentId) ;

    /**
     * Удаляет документ из индекса
     *
     * @param mixed $document
     * @throws SearchIndexerException
     * @return void
     */
    public function remove($document) ;

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
    public function create() ;

    /**
     * Удаляет индекс
     *
     * @return void
     * @throws SearchIndexerException
     * @return void;
     */
    public function destroy() ;

    /**
     * Пересчитывает маппинг индекса
     *
     * @return void
     * @throws SearchIndexerException
     * @return void;
     */
    public function upgrade() ;
}
