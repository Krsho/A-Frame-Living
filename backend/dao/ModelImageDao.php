<?php
/* ModelImageDao – photos of a house model (sort_order 0 = main photo). */

require_once __DIR__ . '/BaseDao.php';

class ModelImageDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('model_images', ['model_id', 'file_path', 'alt_text', 'sort_order']);
    }

    /** All photos of one model, main photo first. */
    public function getForModel(int $modelId): array
    {
        return $this->query(
            'SELECT * FROM model_images WHERE model_id = ? ORDER BY sort_order, id',
            [$modelId]
        );
    }
}
