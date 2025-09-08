<?php

namespace Sunnysideup\PageFavouritesBookmarker\Model;

use Page;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;

class BookmarkUrl extends DataObject
{
    private static $table_name = 'BookmarkUrl';

    private static $db = [
        'Title' => 'Varchar(255)',
        'ImageLink' => 'Varchar(755)',
        'Description' => 'HTMLText',
        'URL' => 'Varchar(755)',
        'SortOrder' => 'Int',
    ];

    private static $has_one = [
        'Page' => Page::class,
    ];
    private static $has_many = [
        'Bookmarks' => Bookmark::class,
    ];

    private static $summary_fields = [
        'ImageRender' => 'Image',
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
        'Bookmarks',
    ];

    private static $casting = [
        'Link' => 'Varchar',
        'AbsoluteLink' => 'ExternalURL',
        'ImageRender' => 'HTMLText',
    ];

    public static function find_or_make_bookmark_url(array $vars): ?BookmarkUrl
    {
        $filter = array_filter(
            [
                'Title' => trim($vars['Title'] ?? ''),
                'URL' => trim($vars['URL'] ?? ''),
                'ImageLink' => trim($vars['ImageLink'] ?? ''),
                'Description' => trim($vars['Description'] ?? ''),
            ]
        );
        $bookmarkUrl = BookmarkUrl::get()->filter($filter)->first();
        if (!$bookmarkUrl) {
            $bookmarkUrl = BookmarkUrl::create($filter);
            if ($bookmarkUrl->IsValidBookmark()) {
                $bookmarkUrl->write();
            }
        }
        return $bookmarkUrl;
    }

    public function getLink(): string
    {
        return $this->toRelativeUrl((string) $this->URL);
    }

    public function getAbsoluteLink(): string
    {
        return Director::absoluteURL($this->toRelativeUrl((string) $this->URL));
    }

    public function getImageRender(): string
    {
        if (! $this->ImageLink) {
            return '';
        }
        return '<img src="' . Director::absoluteURL($this->toRelativeUrl((string) $this->ImageLink)) . '" alt="' . Convert::raw2att($this->Title) . '" height="50" />';
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

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->cleanVars();
        $this->findMatchingPage();
    }

    protected function cleanVars()
    {
        if ($this->isValidUrl((string) $this->URL)) {
            $this->URL = $this->toRelativeUrl($this->URL);
        } else {
            $this->URL = '';
        }
        $this->Title = $this->stripTags($this->Title);
        if ($this->isValidUrl((string) $this->ImageLink)) {
            $this->ImageLink = $this->toRelativeUrl($this->ImageLink);
        } else {
            $this->ImageLink = '';
        }
        $this->Description = Convert::raw2xml($this->Description);
    }


    public function IsValidBookmark(): bool
    {
        return $this->URL && $this->isValidUrl((string) $this->URL) && !empty($this->Title);
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('SortOrder');
        $fields->dataFieldByName('URL')
            ?->setDescription('<a href="' . $this->getLink() . '">Open Link</a>');
        $fields->dataFieldByName('ImageLink')
            ?->setDescription($this->getImageRender());
        if ($this->PageID) {
            $page = $this->Page();
            $fields->dataFieldByName('PageID')
                ?->setDescription('The page this bookmark is linked to. This is set automatically if the URL matches a page on this site. <br />
                    <a href="' . ($page ? $page->Link() : '#') . '">View Page</a>');
        }
        return $fields;
    }

    protected function isValidUrl(string $url): bool
    {
        if (trim($url) === '') {
            return false;
        }
        $test = Director::absoluteURL($url);
        return filter_var($test, FILTER_VALIDATE_URL) !== false;
    }

    protected function stripTags(string $string): string
    {
        return strip_tags($string);
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

    protected function toRelativeUrl(string $url): string
    {
        $s = trim($url);
        if ($s === '') return '';

        // absolute or protocol-relative? (e.g. https:, mailto:, //host)
        if (str_starts_with($s, '//') || preg_match('/^[a-z][a-z0-9+\-.]*:/i', $s)) {
            $forParse = str_starts_with($s, '//') ? 'http:' . $s : $s;
            $path     = (string)(parse_url($forParse, PHP_URL_PATH) ?? '');
            $query    = (string)(parse_url($forParse, PHP_URL_QUERY) ?? '');
            $fragment = (string)(parse_url($forParse, PHP_URL_FRAGMENT) ?? '');

            $path = '/' . ltrim($path, '/');
            return $path
                . ($query !== '' ? '?' . $query : '')
                . ($fragment !== '' ? '#' . $fragment : '');
        }

        // already relative → keep, but ensure leading '/' for paths
        if ($s[0] === '/') return $s;
        return '/' . $s;
    }
}
