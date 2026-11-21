<?php
/* PackageService – the packages a model is sold in (ROH BAU, Turnkey). */

require_once __DIR__ . '/BaseService.php';

class PackageService extends BaseService
{
    private ModelPriceDao $priceDao;

    public function __construct()
    {
        parent::__construct(new PackageDao(), 'Package');
        $this->priceDao = new ModelPriceDao();
    }

    protected function validate(array $data, bool $partial, ?int $id): array
    {
        return (new Validator($data, $partial))
            ->string('code', required: true, max: 30, pattern: '/^[a-z][a-z0-9_]*$/', patternMessage: 'Use lowercase letters, digits and _, e.g. "roh_bau".')
            ->string('name', required: true, max: 60)
            ->string('description', max: 2000)
            ->validate();
    }

    /** The code must be unique. */
    protected function checkRules(array $clean, ?int $id): void
    {
        if (isset($clean['code'])) {
            $existing = $this->dao->getByCode($clean['code']);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                throw new ConflictException('A package with this code already exists.');
            }
        }
    }

    /**
     * Deleting a package would also delete every price in it (ON DELETE CASCADE),
     * so a package that still has prices cannot be deleted.
     */
    protected function beforeDelete(array $row): void
    {
        $prices = $this->priceDao->getForPackage((int) $row['id']);
        if ($prices !== []) {
            throw new ConflictException(sprintf(
                'This package still has %d price(s). Delete the prices first.',
                count($prices)
            ));
        }
    }
}
