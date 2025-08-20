<?php

namespace Sunnysideup\PageFavouritesBookmarker\Model;

use Page;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataObject;

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
        'BookmarkList' => BookmarkList::class,
    ];

    private static $summary_fields = [
        'Title' => 'Page Title',
        'URL' => 'URL',
        'BookmarkList.Title' => 'List',
        'Created.Ago' => 'Created',
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
            if (!empty($parts['path'])) {
                $page = SiteTree::get_by_link($parts['path']);
                if ($page) {
                    $this->PageID = $page->ID;
                }
            }
        }
    }
    public function canCreate($member = null, $context = [])
    {
        return false; // Prevent creation of new bookmarks directly
    }

    public function canEdit($member = null)
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return false;
    }
}
