<?php
/* ProjectService – completed builds: which model was built at which location. */

require_once __DIR__ . '/BaseService.php';

class ProjectService extends BaseService
{
    private HouseModelDao $modelDao;
    private LocationDao $locationDao;

    public function __construct()
    {
        parent::__construct(new ProjectDao(), 'Project');
        $this->modelDao = new HouseModelDao();
        $this->locationDao = new LocationDao();
    }

    /** All projects with the model name and the location (incl. coordinates for the map). */
    public function getAll(): array
    {
        return $this->dao->getAllWithDetails();
    }

    protected function validate(array $data, bool $partial, ?int $id): array
    {
        return (new Validator($data, $partial))
            ->integer('model_id', required: true, min: 1)
            ->integer('location_id', required: true, min: 1)
            ->string('setting', max: 60)
            ->date('completed_on')
            ->validate();
    }

    protected function checkRules(array $clean, ?int $id): void
    {
        if (isset($clean['model_id']) && $this->modelDao->getById($clean['model_id']) === null) {
            $this->fieldError('model_id', 'This house model does not exist.');
        }
        if (isset($clean['location_id']) && $this->locationDao->getById($clean['location_id']) === null) {
            $this->fieldError('location_id', 'This location does not exist.');
        }
        // A completed project cannot be completed in the future.
        if (isset($clean['completed_on']) && $clean['completed_on'] > date('Y-m-d')) {
            $this->fieldError('completed_on', 'Cannot be in the future.');
        }
    }
}
