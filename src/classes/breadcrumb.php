<?php
class Breadcrumb
{
    private $separator;
    private $items = array();

    public function __construct($separator = null)
    {
        $this->separator = $separator ?? ' &gt; ';
        $seoUrl = new SeoUrl(new Languages($_GET['language_code'] ?? ''));
        $this->addItem(BREADCRUMB_HOME, $seoUrl->generate('index.php'));
    }

    /**
     * Add item to breadcrumb
     * @param string $title The title of the breadcrumb item
     * @param string|null $url The URL of the breadcrumb item (optional)
     */
    public function addItem($title, $url = null): void
    {
        $this->items[] = array('title' => $title, 'url' => $url);
    }

    /**
     * Create breadcrumb HTML
     * @return string The HTML for the breadcrumb navigation
     */
    public function render(): string
    {
        $breadcrumbHtml = '<nav class="breadcrumb-nav">';
        $totalItems = count($this->items);
        foreach ($this->items as $index => $item)
        {
            $breadcrumbHtml .= '<a href="' . ($item['url'] ?: '#' . '" rel="nofollow') . '"' . ($index == $totalItems - 1 ? ' class="current"' : '') . '>' . htmlspecialchars($item['title']) . '</a>';

            if ($index < $totalItems - 1)
            {
                $breadcrumbHtml .= $this->separator;
            }
        }
        $breadcrumbHtml .= '</nav>';
        return $breadcrumbHtml;
    }
}
