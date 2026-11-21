<?php
/* FaqService – questions and answers for the FAQ page. */

require_once __DIR__ . '/BaseService.php';

class FaqService extends BaseService
{
    public const CATEGORIES = ['models', 'packages', 'customization', 'locations', 'process'];

    public function __construct()
    {
        parent::__construct(new FaqDao(), 'FAQ entry');
    }

    /** All entries in page order, or only one category. */
    public function getAllFiltered(?string $category): array
    {
        if ($category === null || $category === '') {
            return $this->dao->getAllOrdered();
        }
        if (!in_array($category, self::CATEGORIES, true)) {
            $this->fieldError('category', 'Must be one of: ' . implode(', ', self::CATEGORIES) . '.');
        }
        return $this->dao->getByCategory($category);
    }

    protected function validate(array $data, bool $partial, ?int $id): array
    {
        $clean = (new Validator($data, $partial))
            ->enum('category', self::CATEGORIES, required: true)
            ->string('question', required: true, min: 5, max: 255)
            ->string('answer', required: true, min: 5, max: 5000)
            ->string('link_label', max: 100)
            // Only links inside the site (#/...) or to http(s) pages; no "javascript:" links.
            ->string('link_url', max: 255, pattern: '~^(#/|https?://)\S*$~', patternMessage: 'Must start with #/ or http(s)://.')
            ->integer('sort_order', min: 0, max: 1000)
            ->validate();

        return $clean;
    }

    /** A link needs both a label and a URL. */
    protected function checkRules(array $clean, ?int $id): void
    {
        $current = $id !== null ? $this->getById($id) : [];
        $label = array_key_exists('link_label', $clean) ? $clean['link_label'] : ($current['link_label'] ?? null);
        $url = array_key_exists('link_url', $clean) ? $clean['link_url'] : ($current['link_url'] ?? null);

        if (($label === null) !== ($url === null)) {
            $this->fieldError($label === null ? 'link_label' : 'link_url', 'A link needs both link_label and link_url.');
        }
    }
}
