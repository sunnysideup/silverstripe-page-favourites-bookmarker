<?php

namespace Sunnysideup\PageFavouritesBookmarker\Model;

use Page;

class Bookmark extends DataObject
{
    private static $table_name = 'Bookmark';
    private static $db = [
        'Title' => 'Varchar(255)',
        'URL' => 'Varchar(512)',
        'SortOrder' => 'Int',
    ];

    private static $has_one = [
        'Page' => Page::class,
        'BookmarkSession' => BookmarkSession::class,
    ];

    private static $indexes = [
        'Title' => true,
        'URL' => true,
        'SortOrder' => true,
    ];


    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->SortOrder) {
            $this->SortOrder = 0;
        }
        $this->findMatchingPage();
    }

    protected function findMatchingPage()
    {
        if (! $this->PageID) {
            $parts = parse_url($this->URL);
            if (isset($parts['path'])) {
                $segments = explode('/', trim($parts['path'], '/'));
                $segments = array_filter($segments);
                $page = SiteTree::get_by_link($segments);
                if ($page) {
                    $this->PageID = $page->ID;
                }
            }
        }
    }
}
