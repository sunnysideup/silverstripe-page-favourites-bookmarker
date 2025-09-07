<?php

namespace Sunnysideup\PageFavouritesBookmarker\Model;

use Page;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataObject;

class Bookmark extends DataObject
{
    private static $table_name = 'Bookmark';
    private static $db = [
        'SortOrder' => 'Int',
    ];

    private static $has_one = [
        'BookmarkList' => BookmarkList::class,
        'BookmarkUrl' => BookmarkUrl::class,
    ];

    private static $casting = [
        'Title' => 'Varchar',
        'URL' => 'ExternalURL',
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

    private static $default_sort = '"SortOrder" ASC';

    public static function create_bookmark(int $listID, array $vars): ?Bookmark
    {
        $bookmarkUrl = BookmarkUrl::find_or_make_bookmark_url($vars);
        if (!$bookmarkUrl) {
            return null;
        }
        return self::create_bookmark_from_existing($listID, $bookmarkUrl);
    }
    public static function create_bookmark_from_existing(int $listID, BookmarkUrl $bookmarkUrl): ?Bookmark
    {
        if (! $bookmarkUrl->exists()) {
            return null;
        }
        $filter = [
            'BookmarkUrlID' => $bookmarkUrl->ID,
            'BookmarkListID' => $listID
        ];
        $bookmark = Bookmark::get()->filter($filter)->first();
        if (!$bookmark) {
            $bookmark = Bookmark::create($filter);
            $bookmark->write();
        }

        return $bookmark;
    }



    public function getTitle()
    {
        return $this->BookmarkUrl()->Title ?: '[no title]';
    }

    public function getURL()
    {
        return $this->BookmarkUrl()->URL ?: '[no URL]';
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (! $this->exists()) {
            $maxSort = 0;
            $list = Bookmark::get()->filter(['BookmarkListID' => $this->BookmarkListID]);
            if ($list->exists()) {
                $maxSort = $list->max('SortOrder') ?: 0;
            }
            $this->SortOrder = $maxSort + 1;
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
        return true;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('SortOrder');
        return $fields;
    }
}
