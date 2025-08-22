<?php

namespace Sunnysideup\PageFavouritesBookmarker\Model;

use Page;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataObject;

class BookmarkUrl extends DataObject
{
    private static $table_name = 'BookmarkUrl';
    private static $db = [
        'Title' => 'Varchar(255)',
        'URL' => 'Varchar(512)',
        'SortOrder' => 'Int',
    ];

    private static $has_one = [
        'Page' => Page::class,
        'Bookmark' => Bookmark::class,
    ];
    private static $has_many = [
        'Bookmarks' => Bookmark::class,
    ];

    private static $summary_fields = [
        'Title' => 'Page Title',
        'URL' => 'URL',
        'Bookmarks.Count' => 'Inclusion count',
    ];

    private static $indexes = [
        'Title' => true,
        'URL' => true,
        'SortOrder' => true,
    ];

    private static $cascade_deletes = [
        'Bookmarks' => true,
    ];

    public static function find_or_make_bookmark_url(string $title, string $url): ?BookmarkUrl
    {
        $filter = [
            'Title' => $title,
            'URL' => $url,
        ];
        $bookmarkUrl = BookmarkUrl::get()->filter($filter)->first();
        if (!$bookmarkUrl) {
            $bookmarkUrl = BookmarkUrl::create($filter);
            if ($bookmarkUrl->IsValidBookmark()) {
                $bookmarkUrl->write();
            }
        }
        return $bookmarkUrl;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->isValidUrl($this->URL)) {
            $this->URL = '';
        }
        $this->Title = $this->removeNonAlphaNumericCharacters($this->Title);
        $this->findMatchingPage();
    }

    public function IsValidBookmark(): bool
    {
        return $this->URL && $this->isValidUrl($this->URL) && !empty($this->Title);
    }

    protected function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    protected function removeNonAlphaNumericCharacters(string $string): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $string);
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
