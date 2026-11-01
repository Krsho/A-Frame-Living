<?php
/* HouseModelDao – the A-frame house models (Atlantic, Norway, Scandinavia, Continental). */

require_once __DIR__ . '/BaseDao.php';

class HouseModelDao extends BaseDao
{
    protected array $extraSortColumns = ['created_at', 'updated_at'];

    public function __construct()
    {
        parent::__construct('house_models', [
            'slug', 'name', 'summary', 'area_m2', 'bedrooms', 'bathrooms',
            'footprint_width_m', 'footprint_length_m', 'side_room_width_m', 'side_room_length_m',
            'height_m', 'is_active',
        ]);
    }

    public function getBySlug(string $slug): ?array
    {
        return $this->queryOne('SELECT * FROM house_models WHERE slug = ?', [$slug]);
    }

    /** Models shown on the public website, smallest first. */
    public function getActive(): array
    {
        return $this->query('SELECT * FROM house_models WHERE is_active = 1 ORDER BY area_m2');
    }
}
