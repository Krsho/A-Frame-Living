<?php
/*
 * BaseDao – reusable database methods for all DAO classes.
 *
 * A DAO (Data Access Object) is the only layer that runs SQL. Each entity
 * DAO extends this class and only says which table and columns it uses:
 *
 *   class PackageDao extends BaseDao {
 *       public function __construct() {
 *           parent::__construct('packages', ['code', 'name', 'description']);
 *       }
 *   }
 *
 * Safety rules:
 *   - Every VALUE is sent through a prepared statement (? placeholders).
 *   - Column and table NAMES cannot be placeholders, so they are only taken
 *     from the DAO's own list ($columns). Unknown keys throw an exception.
 */

require_once __DIR__ . '/../config/Database.php';

abstract class BaseDao
{
    protected PDO $connection;

    /** Columns returned by SELECT queries ('*' = all). UserDao overrides this to hide password_hash. */
    protected string $selectColumns = '*';

    /** Read-only columns that getAll() may also sort by, e.g. ['created_at']. */
    protected array $extraSortColumns = [];

    /**
     * @param string      $table      Table name, e.g. 'house_models'
     * @param string[]    $columns    Columns that may be written (INSERT/UPDATE), without the primary key
     * @param string|null $primaryKey Primary key column, or null for link tables with a composite key
     */
    public function __construct(
        protected string $table,
        protected array $columns,
        protected ?string $primaryKey = 'id'
    ) {
        $this->connection = Database::connection();
    }

    // ---------------------------------------------------------------
    // CRUD
    // ---------------------------------------------------------------

    /** READ all rows, optionally sorted and paged. */
    public function getAll(?string $orderBy = null, string $direction = 'ASC', ?int $limit = null, int $offset = 0): array
    {
        $sql = "SELECT {$this->selectColumns} FROM `{$this->table}`";

        $orderBy ??= $this->primaryKey;
        if ($orderBy !== null) {
            $this->assertColumn($orderBy, true);
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY `{$orderBy}` {$direction}";
        }

        $params = [];
        if ($limit !== null) {
            $sql .= ' LIMIT ? OFFSET ?';
            $params = [$limit, $offset];
        }

        return $this->query($sql, $params);
    }

    /** READ one row by primary key, or null if it does not exist. */
    public function getById(int $id): ?array
    {
        $this->assertSingleKey();
        return $this->queryOne(
            "SELECT {$this->selectColumns} FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
    }

    /** CREATE a row. Returns the new row as stored in the database. */
    public function add(array $data): array
    {
        $data = $this->filterColumns($data);
        if ($data === []) {
            throw new InvalidArgumentException("No data given for a new row in {$this->table}.");
        }

        $columns = implode(', ', array_map(fn ($c) => "`{$c}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $this->execute("INSERT INTO `{$this->table}` ({$columns}) VALUES ({$placeholders})", array_values($data));

        return $this->primaryKey !== null
            ? $this->getById((int) $this->connection->lastInsertId())
            : $data;
    }

    /**
     * UPDATE a row. Only the given columns change, so this works for both
     * PUT (all fields) and PATCH (some fields). Returns the updated row, or null if the id does not exist.
     */
    public function update(int $id, array $data): ?array
    {
        $this->assertSingleKey();
        $data = $this->filterColumns($data);
        if ($data === []) {
            throw new InvalidArgumentException("No data given to update in {$this->table}.");
        }

        $assignments = implode(', ', array_map(fn ($c) => "`{$c}` = ?", array_keys($data)));
        $params = [...array_values($data), $id];
        $this->execute("UPDATE `{$this->table}` SET {$assignments} WHERE `{$this->primaryKey}` = ?", $params);

        return $this->getById($id);
    }

    /** DELETE a row. Returns true if a row was deleted, false if the id did not exist. */
    public function delete(int $id): bool
    {
        $this->assertSingleKey();
        return $this->execute("DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?", [$id]) > 0;
    }

    /** Number of rows in the table. */
    public function count(): int
    {
        return (int) $this->queryOne("SELECT COUNT(*) AS total FROM `{$this->table}`")['total'];
    }

    // ---------------------------------------------------------------
    // Helpers for the entity DAOs (always with ? placeholders)
    // ---------------------------------------------------------------

    /** Runs a SELECT and returns all rows. */
    protected function query(string $sql, array $params = []): array
    {
        $statement = $this->connection->prepare($sql);
        $this->bindAll($statement, $params);
        $statement->execute();
        return $statement->fetchAll();
    }

    /** Runs a SELECT and returns the first row, or null. */
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $rows = $this->query($sql, $params);
        return $rows[0] ?? null;
    }

    /** Runs INSERT/UPDATE/DELETE and returns the number of affected rows. */
    protected function execute(string $sql, array $params = []): int
    {
        $statement = $this->connection->prepare($sql);
        $this->bindAll($statement, $params);
        $statement->execute();
        return $statement->rowCount();
    }

    /** Binds values by position with the right PDO type (integers must stay integers for LIMIT). */
    private function bindAll(PDOStatement $statement, array $params): void
    {
        foreach (array_values($params) as $index => $value) {
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $statement->bindValue($index + 1, $value, $type);
        }
    }

    /** Keeps only allowed columns; throws if an unknown column is given. */
    protected function filterColumns(array $data): array
    {
        foreach (array_keys($data) as $column) {
            $this->assertColumn($column);
        }
        return $data;
    }

    protected function assertColumn(string $column, bool $allowPrimaryKey = false): void
    {
        $allowed = $allowPrimaryKey
            ? [...$this->columns, ...$this->extraSortColumns, ...($this->primaryKey !== null ? [$this->primaryKey] : [])]
            : $this->columns;

        if (!in_array($column, $allowed, true)) {
            throw new InvalidArgumentException("Unknown column '{$column}' for table {$this->table}.");
        }
    }

    private function assertSingleKey(): void
    {
        if ($this->primaryKey === null) {
            throw new LogicException("{$this->table} has a composite key; use the DAO's own methods.");
        }
    }
}
