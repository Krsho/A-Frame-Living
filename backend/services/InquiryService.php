<?php
/*
 * InquiryService – messages sent through the contact form.
 *
 * - Anyone can send an inquiry; it always starts with the status "new".
 * - The admin changes the status (new -> in_progress -> answered -> closed).
 * - In Milestone 3 the sender's user_id is sent in the body; from Milestone 4
 *   it is taken from the JWT of the logged-in user.
 */

require_once __DIR__ . '/BaseService.php';

class InquiryService extends BaseService
{
    public const STATUSES = ['new', 'in_progress', 'answered', 'closed'];

    private UserDao $userDao;
    private HouseModelDao $modelDao;

    public function __construct()
    {
        parent::__construct(new InquiryDao(), 'Inquiry');
        $this->userDao = new UserDao();
        $this->modelDao = new HouseModelDao();
    }

    /** All inquiries, newest first, optionally only one status. */
    public function getAllFiltered(?string $status): array
    {
        if ($status === null || $status === '') {
            return $this->dao->getAllWithModel();
        }
        if (!in_array($status, self::STATUSES, true)) {
            $this->fieldError('status', 'Must be one of: ' . implode(', ', self::STATUSES) . '.');
        }
        return $this->dao->getByStatus($status);
    }

    protected function validate(array $data, bool $partial, ?int $id): array
    {
        $validator = (new Validator($data, $partial))
            ->integer('user_id', min: 1)
            ->integer('model_id', min: 1)
            ->string('name', required: true, min: 2, max: 120)
            ->email('email', required: true)
            ->string('subject', required: true, min: 3, max: 150)
            ->string('message', required: true, min: 10, max: 5000);

        // The status can only be changed later (by the admin), not chosen when sending.
        if ($id !== null) {
            $validator->enum('status', self::STATUSES);
        }
        return $validator->validate();
    }

    protected function checkRules(array $clean, ?int $id): void
    {
        if (isset($clean['user_id']) && $this->userDao->getById($clean['user_id']) === null) {
            $this->fieldError('user_id', 'This user does not exist.');
        }
        if (isset($clean['model_id']) && $this->modelDao->getById($clean['model_id']) === null) {
            $this->fieldError('model_id', 'This house model does not exist.');
        }
    }
}
