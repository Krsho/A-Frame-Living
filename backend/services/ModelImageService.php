<?php
/*
 * ModelImageService – photos of a house model.
 *
 * In Milestone 3 a photo is a path to a file in frontend/assets/images/
 * (e.g. "assets/images/norway/norway1.JPG"); uploading files is not part of this milestone.
 * sort_order 0 is the main photo.
 */

require_once __DIR__ . '/BaseService.php';

class ModelImageService extends BaseService
{
    private HouseModelDao $modelDao;

    public function __construct()
    {
        parent::__construct(new ModelImageDao(), 'Photo');
        $this->modelDao = new HouseModelDao();
    }

    /** All photos of one model, main photo first. */
    public function getForModel(int $modelId): array
    {
        $this->assertModelExists($modelId);
        return $this->dao->getForModel($modelId);
    }

    /** Adds a photo to a model. The model comes from the URL, not from the body. */
    public function createForModel(int $modelId, array $data): array
    {
        $this->assertModelExists($modelId);
        $clean = $this->validate($data, false, null);
        $clean['model_id'] = $modelId;
        return $this->dao->add($clean);
    }

    protected function validate(array $data, bool $partial, ?int $id): array
    {
        return (new Validator($data, $partial))
            // Only image files inside assets/images/, and no ".." to leave that folder.
            ->string('file_path', required: true, max: 255,
                pattern: '~^assets/images/[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*\.(jpe?g|png|webp)$~i',
                patternMessage: 'Must be an image path such as assets/images/norway/norway1.JPG.')
            ->string('alt_text', required: true, max: 255)
            ->integer('sort_order', min: 0, max: 1000)
            ->validate();
    }

    private function assertModelExists(int $modelId): void
    {
        if ($this->modelDao->getById($modelId) === null) {
            throw new NotFoundException('House model not found.');
        }
    }
}
