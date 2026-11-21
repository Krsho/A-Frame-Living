<?php
/* FeatureService – rooms and features such as "Kitchen" or "Fireplace". */

require_once __DIR__ . '/BaseService.php';

class FeatureService extends BaseService
{
    public const CATEGORIES = ['room', 'construction', 'equipment'];

    private ModelFeatureDao $modelFeatureDao;

    public function __construct()
    {
        parent::__construct(new FeatureDao(), 'Feature');
        $this->modelFeatureDao = new ModelFeatureDao();
    }

    /** All features, or only one category. */
    public function getAllFiltered(?string $category): array
    {
        if ($category === null || $category === '') {
            return $this->dao->getAll('name');
        }
        if (!in_array($category, self::CATEGORIES, true)) {
            $this->fieldError('category', 'Must be one of: ' . implode(', ', self::CATEGORIES) . '.');
        }
        return $this->dao->getByCategory($category);
    }

    /** One feature together with the models that have it. */
    public function getWithModels(int $id): array
    {
        $feature = $this->getById($id);
        $feature['models'] = $this->modelFeatureDao->getModelsWithFeature($id);
        return $feature;
    }

    protected function validate(array $data, bool $partial, ?int $id): array
    {
        return (new Validator($data, $partial))
            ->string('name', required: true, max: 100)
            ->enum('category', self::CATEGORIES, required: true)
            ->validate();
    }

    /** The name must be unique. */
    protected function checkRules(array $clean, ?int $id): void
    {
        if (isset($clean['name'])) {
            $existing = $this->dao->getByName($clean['name']);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                throw new ConflictException('A feature with this name already exists.');
            }
        }
    }

    // Deleting a feature also removes it from every model (ON DELETE CASCADE), which is intended.
}
