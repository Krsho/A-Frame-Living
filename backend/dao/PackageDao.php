<?php
/* PackageDao – the packages every model is sold in (ROH BAU, Turnkey). */

require_once __DIR__ . '/BaseDao.php';

class PackageDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('packages', ['code', 'name', 'description']);
    }

    public function getByCode(string $code): ?array
    {
        return $this->queryOne('SELECT * FROM packages WHERE code = ?', [$code]);
    }
}
