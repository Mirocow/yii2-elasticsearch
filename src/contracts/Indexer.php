<?php
namespace mirocow\elasticsearch\contracts;

use mirocow\elasticsearch\exceptions\SearchIndexerException;

/**
 * Interface Indexer
 * @package mirocow\elasticsearch\contracts
 */
interface Indexer
{
    /**
     * Регистрирует поисковый иднекс в индексаторе.
     * Методы индексатора сами определяют, к какому индексу относится документ.
     * @see SearchIndex::accepts()
     *
     * @param Index $index
     * @throws SearchIndexerException
     * @return void
     */
    public function registerIndex(Index $index) ;

    /**
     * Добавляет документ в индекс
     *
     * @param mixed $document
     * @throws SearchIndexerException
     * @return void
     */
    public function index($document) ;

    /**
     * Удаляет документ из индекса
     *
     * @param mixed $document
     * @throws SearchIndexerException
     * @return void
     */
    public function remove($document) ;

    /**
     * Пересоздает все индексы и индексирует все документы
     *
     * @return void
     */
    public function rebuild(string $indexName = '') ;

    /**
     * Индексирует все документы в указанном индексе
     *
     * @param string $indexName
     * @return void
     */
    public function populate(string $indexName = '') ;

    /**
     * Инициализирует индекс по его названию
     *
     * @param string $indexName
     * @throws SearchIndexerException
     * @return void
     */
    public function createIndex(string $indexName = '') ;

    /**
     * Получает объект созданного индекса
     * @param string $indexName
     * @return mixed
     */
    public function getIndex(string $indexName = '') ;

    /**
     * Удаляет индекс по его названию
     *
     * @param string $indexName
     * @throws SearchIndexerException
     * @return void
     */
    public function destroyIndex(string $indexName = '') ;


    /**
     * Перестраивает индекс по его названию
     *
     * @param string $indexName
     * @throws SearchIndexerException
     * @return void
     */
    public function upgradeIndex(string $indexName = '') ;

}
