<?php
/* ProjectDao – completed builds: one model at one location. */

require_once __DIR__ . '/BaseDao.php';

class ProjectDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('projects', ['model_id', 'location_id', 'setting', 'completed_on']);
    }

    /** All projects with the model name and the location details (for lists and the map). */
    public function getAllWithDetails(): array
    {
        return $this->query(
            'SELECT p.id, p.setting, p.completed_on,
                    h.id AS model_id, h.slug AS model_slug, h.name AS model_name,
                    l.id AS location_id, l.city, l.region, l.country, l.latitude, l.longitude
             FROM projects p
             JOIN house_models h ON h.id = p.model_id
             JOIN locations l ON l.id = p.location_id
             ORDER BY p.id'
        );
    }

    /** Projects of one model, with their location. */
    public function getForModel(int $modelId): array
    {
        return $this->query(
            'SELECT p.*, l.city, l.region, l.country
             FROM projects p
             JOIN locations l ON l.id = p.location_id
             WHERE p.model_id = ?
             ORDER BY l.city',
            [$modelId]
        );
    }
}
