<?php

namespace Sunnysideup\PageFavouritesBookmarker\Model;

use Page;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Sunnysideup\PageFavouritesBookmarker\Api\CodeMaker;
use Sunnysideup\PageFavouritesBookmarker\Control\BookmarkController;

class BookmarkList extends DataObject
{
    private static $table_name = 'BookmarkList';
    private static $db = [
        'Code' => 'Varchar(12)',
    ];
    private static $casting = [
        'Title' => 'Varchar',
    ];

    private static $has_one = [
        'Member' => Member::class,
    ];

    private static $has_many = [
        'Bookmarks' => Bookmark::class,
    ];

    private static $summary_fields = [
        'Title' => 'Who',
        'Created.Ago' => 'First Entry',
        'LastEdited.Ago' => 'Last Edited',
        'Bookmarks.Count' => 'Number of Bookmarks',
    ];

    private static $default_sort = '"ID" DESC';

    private static $cascade_deletes = [
        'Bookmarks',
    ];

    private static $indexes = [
        'Code',
    ];

    public function removeByUrl(string $url)
    {
        $bookmarksUrls = BookmarkUrl::get()->filter(['URL' => $url])->columnUnique('ID');
        if (count($bookmarksUrls)) {
            $bookmarks = $this->Bookmarks()->filter(['BookmarkUrlID' => $bookmarksUrls]);
            foreach ($bookmarks as $bookmark) {
                $bookmark->delete();
            }
        }
    }

    public function addByUrlAndTitle(string $url, string $title): ?Bookmark
    {
        return Bookmark::create_bookmark($url, $title, $this->ID);
    }

    public function addManyByBookmarkUrlIds($array): void
    {
        $ids = array_filter(explode(',', $array), 'is_numeric');
        foreach ($ids as $id) {
            $bookmarkUrl = BookmarkUrl::get()->byID(intval($id));
            if ($bookmarkUrl) {
                $this->addByUrlAndTitle($bookmarkUrl->URL, $bookmarkUrl->Title);
            }
        }
    }

    public function getTitle()
    {
        // $count = $this->Bookmarks()->count();
        // if ($count === 0) {
        //     return 'Empty Session';
        // }
        // $s = $count > 1 ? 's' : '';
        if ($this->MemberID) {
            return $this->Member()->getName();
        }
        return 'Anonymous';
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab(
            'Root.Main',
            [
                ReadonlyField::create('ShareLinkNice', 'Share Link', DBHTMLText::create()
                    ->setValue('<a href="' . $this->ShareLink() . '" target="_blank">' . $this->ShareLink() . '</a>')),
            ]
        );
        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->Code) {
            $this->Code = CodeMaker::make_alpha_num_code(12);
        }
        if (! $this->MemberID && Security::getCurrentUser()) {
            $this->MemberID = Security::getCurrentUser()->ID;
        }
    }

    public function canCreate($member = null, $context = [])
    {
        return false; // Prevent creation of new bookmark lists directly
    }

    public function canEdit($member = null)
    {
        return true;
    }

    public function canDelete($member = null)
    {
        return true; // Prevent deletion of bookmark lists directly
    }

    public function ShareLink()
    {
        $items = $this->Bookmarks()->columnUnique('BookmarkUrlID');
        return BookmarkController::my_link('share' . '/' . implode(',', $items));
    }

    public function BookmarksAsArray(): array
    {
        $data = [];
        foreach ($this->Bookmarks() as $bookmark) {
            $url = $bookmark->BookmarkUrl();
            if ($url->IsValidBookmark()) {
                $data[] = [
                    'title' => $url->Title,
                    'url' => $url->URL,
                    'ts' => strtotime($bookmark->Created),
                ];
            }
        }
        return $data;
    }
}
