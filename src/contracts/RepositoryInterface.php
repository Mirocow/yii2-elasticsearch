<?php
namespace mirocow\elasticsearch\contracts;

use common\repositories\exceptions\EntityNotFoundException;
use yii\db\ActiveRecord;

/**
 * Interface RepositoryInterface
 * @package mirocow\elasticsearch\contracts
 */
interface RepositoryInterface
{
    /**
     * @param int $id
     * @throws EntityNotFoundException
     * @return ActiveRecord
     */
    public function get(int $id);

    /**
     * @return iterable
     */
    public function ids();

    /**
     * @return int
     */
    public function count();
}