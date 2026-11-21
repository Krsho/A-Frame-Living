<?php
/*
 * HouseModelService – the A-frame models and the data that belongs to them:
 * prices (per package), features and photos.
 *
 * Rules:
 * - The slug (used in URLs such as #/houses/norway) is unique and URL-friendly.
 * - A model that has completed projects cannot be deleted; set is_active = false instead.
 * - A price must be 0 or more and belongs to an existing package.
 */

require_once __DIR__ . '/BaseService.php';

class HouseModelService extends BaseService
{
    private ModelPriceDao $priceDao;
    private ModelFeatureDao $modelFeatureDao;
    private ModelImageDao $imageDao;
    private ProjectDao $projectDao;
    private PackageDao $packageDao;
    private FeatureDao $featureDao;

    public function __construct()
    {
        parent::__construct(new HouseModelDao(), 'House model');
        $this->priceDao = new ModelPriceDao();
        $this->modelFeatureDao = new ModelFeatureDao();
        $this->imageDao = new ModelImageDao();
        $this->projectDao = new ProjectDao();
        $this->packageDao = new PackageDao();
        $this->featureDao = new FeatureDao();
    }

    // ---------------------------------------------------------------
    // Models
    // ---------------------------------------------------------------

    /**
     * Models for the catalogue, smallest first, each with its prices and main photo.
     * Inactive models are only included when $includeInactive is true (for the admin).
     */
    public function getCatalogue(bool $includeInactive): array
    {
        $models = $includeInactive ? $this->dao->getAll('area_m2') : $this->dao->getActive();

        foreach ($models as &$model) {
            $model['prices'] = $this->priceDao->getForModel((int) $model['id']);
            $images = $this->imageDao->getForModel((int) $model['id']);
            $model['main_image'] = $images[0] ?? null;
        }
        return $models;
    }

    /** One model by id (e.g. "2") or slug (e.g. "norway"), with prices, features, photos and projects. */
    public function getDetails(string $idOrSlug): array
    {
        $model = ctype_digit($idOrSlug)
            ? $this->dao->getById((int) $idOrSlug)
            : $this->dao->getBySlug($idOrSlug);

        if ($model === null) {
            throw new NotFoundException('House model not found.');
        }

        $id = (int) $model['id'];
        $model['prices'] = $this->priceDao->getForModel($id);
        $model['features'] = $this->modelFeatureDao->getFeaturesForModel($id);
        $model['images'] = $this->imageDao->getForModel($id);
        $model['projects'] = $this->projectDao->getForModel($id);
        return $model;
    }

    protected function validate(array $data, bool $partial, ?int $id): array
    {
        return (new Validator($data, $partial))
            ->string('slug', required: true, max: 60, pattern: '/^[a-z0-9]+(-[a-z0-9]+)*$/', patternMessage: 'Use lowercase letters, digits and dashes, e.g. "norway" or "norway-plus".')
            ->string('name', required: true, max: 100)
            ->string('summary', max: 2000)
            ->decimal('area_m2', required: true, min: 1, max: 9999.99)
            ->integer('bedrooms', required: true, min: 0, max: 20)
            ->integer('bathrooms', required: true, min: 0, max: 20)
            ->decimal('footprint_width_m', required: true, min: 0.5, max: 9999.99)
            ->decimal('footprint_length_m', required: true, min: 0.5, max: 9999.99)
            ->decimal('side_room_width_m', min: 0.5, max: 9999.99)
            ->decimal('side_room_length_m', min: 0.5, max: 9999.99)
            ->decimal('height_m', required: true, min: 0.5, max: 9999.99)
            ->boolean('is_active')
            ->validate();
    }

    /** The slug must be unique. */
    protected function checkRules(array $clean, ?int $id): void
    {
        if (isset($clean['slug'])) {
            $existing = $this->dao->getBySlug($clean['slug']);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                throw new ConflictException('A house model with this slug already exists.');
            }
        }
    }

    protected function beforeDelete(array $row): void
    {
        $projects = $this->projectDao->getForModel((int) $row['id']);
        if ($projects !== []) {
            throw new ConflictException(sprintf(
                'This model has %d completed project(s), so it cannot be deleted. Set is_active to false to hide it instead.',
                count($projects)
            ));
        }
        // Its prices, feature links and photos are deleted with it (ON DELETE CASCADE).
    }

    // ---------------------------------------------------------------
    // Prices (model_prices: one price per model and package)
    // ---------------------------------------------------------------

    public function getPrices(int $modelId): array
    {
        $this->getById($modelId);
        return $this->priceDao->getForModel($modelId);
    }

    /** Creates or changes the price of a model in a package. */
    public function setPrice(int $modelId, int $packageId, array $data): array
    {
        $this->getById($modelId);
        if ($this->packageDao->getById($packageId) === null) {
            throw new NotFoundException('Package not found.');
        }
        $clean = (new Validator($data))
            ->decimal('price_eur', required: true, min: 0, max: 99999999.99)
            ->validate();

        return $this->priceDao->setPrice($modelId, $packageId, (float) $clean['price_eur']);
    }

    public function deletePrice(int $modelId, int $packageId): void
    {
        if (!$this->priceDao->deletePrice($modelId, $packageId)) {
            throw new NotFoundException('This model has no price in this package.');
        }
    }

    // ---------------------------------------------------------------
    // Features (model_features: which model has which feature)
    // ---------------------------------------------------------------

    public function getFeatures(int $modelId): array
    {
        $this->getById($modelId);
        return $this->modelFeatureDao->getFeaturesForModel($modelId);
    }

    /** Gives a model a feature. $data = ['feature_id' => 3]. Returns the model's features. */
    public function addFeature(int $modelId, array $data): array
    {
        $this->getById($modelId);
        $clean = (new Validator($data))
            ->integer('feature_id', required: true, min: 1)
            ->validate();

        if ($this->featureDao->getById($clean['feature_id']) === null) {
            $this->fieldError('feature_id', 'This feature does not exist.');
        }
        if (!$this->modelFeatureDao->attach($modelId, $clean['feature_id'])) {
            throw new ConflictException('This model already has this feature.');
        }
        return $this->modelFeatureDao->getFeaturesForModel($modelId);
    }

    public function removeFeature(int $modelId, int $featureId): void
    {
        if (!$this->modelFeatureDao->detach($modelId, $featureId)) {
            throw new NotFoundException('This model does not have this feature.');
        }
    }
}
