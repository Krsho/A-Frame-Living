<?php
/*
 * ModelPriceDao – the price of a model in a package.
 * The table has a composite key (model_id + package_id), so it uses its own
 * methods instead of BaseDao's id-based getById/update/delete.
 */

require_once __DIR__ . '/BaseDao.php';

class ModelPriceDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('model_prices', ['model_id', 'package_id', 'price_eur'], null);
    }

    /** All prices of one model with the package names. */
    public function getForModel(int $modelId): array
    {
        return $this->query(
            'SELECT mp.model_id, mp.package_id, mp.price_eur, p.code AS package_code, p.name AS package_name
             FROM model_prices mp
             JOIN packages p ON p.id = mp.package_id
             WHERE mp.model_id = ?
             ORDER BY p.id',
            [$modelId]
        );
    }

    public function getPrice(int $modelId, int $packageId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM model_prices WHERE model_id = ? AND package_id = ?',
            [$modelId, $packageId]
        );
    }

    /** CREATE or UPDATE: saves the price, inserting the row if it does not exist yet. */
    public function setPrice(int $modelId, int $packageId, float $priceEur): array
    {
        $this->execute(
            'INSERT INTO model_prices (model_id, package_id, price_eur) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE price_eur = VALUES(price_eur)',
            [$modelId, $packageId, $priceEur]
        );
        return $this->getPrice($modelId, $packageId);
    }

    public function deletePrice(int $modelId, int $packageId): bool
    {
        return $this->execute(
            'DELETE FROM model_prices WHERE model_id = ? AND package_id = ?',
            [$modelId, $packageId]
        ) > 0;
    }
}
