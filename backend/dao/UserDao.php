<?php
/*
 * UserDao – customer and admin accounts.
 *
 * Normal reads never return password_hash. Only getByEmailWithPassword()
 * includes it, for checking a password at login (Milestone 4).
 */

require_once __DIR__ . '/BaseDao.php';

class UserDao extends BaseDao
{
    protected string $selectColumns = 'id, first_name, last_name, email, phone, role, created_at, updated_at';
    protected array $extraSortColumns = ['created_at', 'updated_at'];

    public function __construct()
    {
        parent::__construct('users', ['first_name', 'last_name', 'email', 'password_hash', 'phone', 'role']);
    }

    public function getByEmail(string $email): ?array
    {
        return $this->queryOne("SELECT {$this->selectColumns} FROM users WHERE email = ?", [$email]);
    }

    /** Includes password_hash – use only to verify a login. */
    public function getByEmailWithPassword(string $email): ?array
    {
        return $this->queryOne('SELECT * FROM users WHERE email = ?', [$email]);
    }

    /** @param string $role 'customer' or 'admin' */
    public function getByRole(string $role): array
    {
        return $this->query("SELECT {$this->selectColumns} FROM users WHERE role = ? ORDER BY id", [$role]);
    }
}
