<?php
/*
 * ModelFeatureDao – which model has which feature.
 * Composite key (model_id + feature_id): rows are added with attach() and removed with detach().
 */

require_once __DIR__ . '/BaseDao.php';

class ModelFeatureDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('model_features', ['model_id', 'feature_id'], null);
    }

    /** Features of one model, rooms first. */
    public function getFeaturesForModel(int $modelId): array
    {
        return $this->query(
            "SELECT f.*
             FROM model_features mf
             JOIN features f ON f.id = mf.feature_id
             WHERE mf.model_id = ?
             ORDER BY FIELD(f.category, 'room', 'construction', 'equipment'), f.name",
            [$modelId]
        );
    }

    /** Models that have a given feature. */
    public function getModelsWithFeature(int $featureId): array
    {
        return $this->query(
            'SELECT h.id, h.slug, h.name
             FROM model_features mf
             JOIN house_models h ON h.id = mf.model_id
             WHERE mf.feature_id = ?
             ORDER BY h.area_m2',
            [$featureId]
        );
    }

    /** Gives a model a feature. Returns false if it already had it. */
    public function attach(int $modelId, int $featureId): bool
    {
        return $this->execute(
            'INSERT IGNORE INTO model_features (model_id, feature_id) VALUES (?, ?)',
            [$modelId, $featureId]
        ) > 0;
    }

    /** Removes a feature from a model. Returns false if the model did not have it. */
    public function detach(int $modelId, int $featureId): bool
    {
        return $this->execute(
            'DELETE FROM model_features WHERE model_id = ? AND feature_id = ?',
            [$modelId, $featureId]
        ) > 0;
    }
}
