<?php
/*
 * BaseService – the reusable part of every service.
 *
 * A service holds the business logic of one entity: it validates the data,
 * checks rules that need the database (e.g. "this e-mail is already used"),
 * and only then calls its DAO. Services are the only layer that calls DAOs;
 * routes call services.
 *
 * A child service only has to say how its data is validated:
 *
 *   class FeatureService extends BaseService {
 *       protected function validate(array $data, bool $partial, ?int $id): array { ... }
 *   }
 *
 * and may add rules in checkRules() (before saving) and beforeDelete().
 * On errors it throws an HttpException, e.g. NotFoundException (404).
 */

require_once __DIR__ . '/Validator.php';

abstract class BaseService
{
    /**
     * @param BaseDao $dao        The DAO of this entity
     * @param string  $entityName Used in messages, e.g. "Feature not found."
     */
    public function __construct(protected BaseDao $dao, protected string $entityName)
    {
    }

    /**
     * Validates the data sent by the client and returns the cleaned values.
     *
     * @param bool     $partial true for PATCH: only the sent fields are checked
     * @param int|null $id      null when creating, the row id when updating
     */
    abstract protected function validate(array $data, bool $partial, ?int $id): array;

    /** Rules that need the database, e.g. unique values. Runs after validate(). Throws on errors. */
    protected function checkRules(array $clean, ?int $id): void
    {
    }

    /** Rules that must hold before a row may be deleted. Throws on errors. */
    protected function beforeDelete(array $row): void
    {
    }

    // ---------------------------------------------------------------
    // CRUD used by the routes
    // ---------------------------------------------------------------

    public function getAll(): array
    {
        return $this->dao->getAll();
    }

    /** Returns one row or throws NotFoundException (404). */
    public function getById(int $id): array
    {
        $row = $this->dao->getById($id);
        if ($row === null) {
            throw new NotFoundException("{$this->entityName} not found.");
        }
        return $row;
    }

    /** POST – validates and saves a new row. Returns the saved row. */
    public function create(array $data): array
    {
        $clean = $this->validate($data, false, null);
        $this->checkRules($clean, null);
        return $this->dao->add($clean);
    }

    /** PUT ($partial = false: all required fields) or PATCH ($partial = true: only the sent fields). */
    public function update(int $id, array $data, bool $partial = false): array
    {
        $this->getById($id); // 404 if it does not exist
        $clean = $this->validate($data, $partial, $id);
        $this->checkRules($clean, $id);
        return $this->dao->update($id, $clean);
    }

    /** DELETE – removes the row. Throws 404 if it does not exist, or 409 if a rule forbids it. */
    public function delete(int $id): void
    {
        $row = $this->getById($id);
        $this->beforeDelete($row);
        $this->dao->delete($id);
    }

    // ---------------------------------------------------------------
    // Helpers for child services
    // ---------------------------------------------------------------

    /** Throws a 400 error for one field, e.g. when a referenced row does not exist. */
    protected function fieldError(string $field, string $message): never
    {
        throw new ValidationException([$field => $message]);
    }
}
