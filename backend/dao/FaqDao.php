<?php
/* FaqDao – questions and answers for the FAQ page (added in Milestone 3). */

require_once __DIR__ . '/BaseDao.php';

class FaqDao extends BaseDao
{
    /** The order of the categories on the FAQ page. */
    private const CATEGORY_ORDER = "FIELD(category, 'models', 'packages', 'customization', 'locations', 'process')";

    public function __construct()
    {
        parent::__construct('faqs', ['category', 'question', 'answer', 'link_label', 'link_url', 'sort_order']);
    }

    /** All questions in the order they appear on the FAQ page. */
    public function getAllOrdered(): array
    {
        return $this->query('SELECT * FROM faqs ORDER BY ' . self::CATEGORY_ORDER . ', sort_order, id');
    }

    /** @param string $category 'models', 'packages', 'customization', 'locations' or 'process' */
    public function getByCategory(string $category): array
    {
        return $this->query('SELECT * FROM faqs WHERE category = ? ORDER BY sort_order, id', [$category]);
    }
}
