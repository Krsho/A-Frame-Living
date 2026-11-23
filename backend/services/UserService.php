<?php
/*
 * UserService – customer and admin accounts.
 *
 * - Passwords are hashed with password_hash() before saving; the hash is never returned.
 * - E-mails are unique.
 * - The last admin cannot be deleted or turned into a customer, so the site always has an admin.
 *
 * Registration and login with JWT come in Milestone 4 (AuthService).
 */

require_once __DIR__ . '/BaseService.php';

class UserService extends BaseService
{
    public const ROLES = ['customer', 'admin'];

    private InquiryDao $inquiryDao;

    public function __construct()
    {
        parent::__construct(new UserDao(), 'User');
        $this->inquiryDao = new InquiryDao();
    }

    /** The inquiries a user has sent, newest first. */
    public function getInquiries(int $userId): array
    {
        $this->getById($userId);
        return $this->inquiryDao->getForUser($userId);
    }

    protected function validate(array $data, bool $partial, ?int $id): array
    {
        $isNew = $id === null;
        $clean = (new Validator($data, $partial))
            ->string('first_name', required: true, max: 60)
            ->string('last_name', required: true, max: 60)
            ->email('email', required: true)
            // Required for a new account; when updating, a password is only sent to change it.
            ->password('password', required: $isNew)
            ->string('phone', max: 20, pattern: '/^\+?[0-9 ()\/-]{6,20}$/', patternMessage: 'Must be a phone number, e.g. +387 61 000 000.')
            ->enum('role', self::ROLES)
            ->validate();

        // Store only the hash, under the column name the database uses.
        if (isset($clean['password'])) {
            $clean['password_hash'] = password_hash($clean['password'], PASSWORD_DEFAULT);
        }
        unset($clean['password']);

        return $clean;
    }

    protected function checkRules(array $clean, ?int $id): void
    {
        if (isset($clean['email'])) {
            $existing = $this->dao->getByEmail($clean['email']);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                throw new ConflictException('An account with this e-mail already exists.');
            }
        }

        // Changing the role of the last admin to "customer" would leave the site without an admin.
        if ($id !== null && ($clean['role'] ?? null) === 'customer' && $this->isLastAdmin($id)) {
            throw new ConflictException('This is the last admin account, so its role cannot be changed.');
        }
    }

    protected function beforeDelete(array $row): void
    {
        if ($this->isLastAdmin((int) $row['id'])) {
            throw new ConflictException('This is the last admin account, so it cannot be deleted.');
        }
        // The user's inquiries are kept; their user_id becomes NULL (ON DELETE SET NULL).
    }

    private function isLastAdmin(int $userId): bool
    {
        $admins = $this->dao->getByRole('admin');
        return count($admins) === 1 && (int) $admins[0]['id'] === $userId;
    }
}
