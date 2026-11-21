<?php
/* FeatureDao – rooms and features such as "Kitchen" or "Fireplace". */

require_once __DIR__ . '/BaseDao.php';

class FeatureDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('features', ['name', 'category']);
    }

    /** @param string $category 'room', 'construction' or 'equipment' */
    public function getByCategory(string $category): array
    {
        return $this->query('SELECT * FROM features WHERE category = ? ORDER BY name', [$category]);
    }

    public function getByName(string $name): ?array
    {
        return $this->queryOne('SELECT * FROM features WHERE name = ?', [$name]);
    }
}
