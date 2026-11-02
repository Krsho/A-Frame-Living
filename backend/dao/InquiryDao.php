<?php
/* InquiryDao – messages sent through the contact form. */

require_once __DIR__ . '/BaseDao.php';

class InquiryDao extends BaseDao
{
    protected array $extraSortColumns = ['created_at', 'updated_at'];

    public function __construct()
    {
        parent::__construct('inquiries', ['user_id', 'model_id', 'name', 'email', 'subject', 'message', 'status']);
    }

    /** Inquiries of one user, newest first (for the profile page). */
    public function getForUser(int $userId): array
    {
        return $this->query(
            'SELECT i.*, h.name AS model_name, h.slug AS model_slug
             FROM inquiries i
             LEFT JOIN house_models h ON h.id = i.model_id
             WHERE i.user_id = ?
             ORDER BY i.created_at DESC, i.id DESC',
            [$userId]
        );
    }

    /** All inquiries with the model name, newest first (for the admin dashboard). */
    public function getAllWithModel(): array
    {
        return $this->query(
            'SELECT i.*, h.name AS model_name, h.slug AS model_slug
             FROM inquiries i
             LEFT JOIN house_models h ON h.id = i.model_id
             ORDER BY i.created_at DESC, i.id DESC'
        );
    }

    /** @param string $status 'new', 'in_progress', 'answered' or 'closed' */
    public function getByStatus(string $status): array
    {
        return $this->query('SELECT * FROM inquiries WHERE status = ? ORDER BY created_at DESC, id DESC', [$status]);
    }

    public function updateStatus(int $id, string $status): ?array
    {
        return $this->update($id, ['status' => $status]);
    }
}
