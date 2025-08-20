<?php

namespace Sunnysideup\PageFavouritesBookmarker\Model;

use Page;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Sunnysideup\PageFavouritesBookmarker\Api\CodeMaker;

class BookmarkList extends DataObject
{
    private static $table_name = 'BookmarkList';


    private static $db = [
        'Code' => 'Varchar(12)',
        'Session' => 'Varchar(32)',
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

    public function removeByUrl(string $url)
    {
        $bookmarks = $this->Bookmarks()->filter(['URL' => $url]);
        foreach ($bookmarks as $bookmark) {
            $bookmark->delete();
        }
    }

    public function addByUrlAndTitle(string $url, string $title): Bookmark
    {
        $filter = [
            'URL' => $url,
        ];
        $bookmark = $this->Bookmarks()->filter($filter)->first();
        if (!$bookmark) {
            $maxSort = 0;
            if ($this->Bookmarks()->exists()) {
                $maxSort = ($this->Bookmarks()->max('SortOrder') ?: 0) + 1;
            }
            $bookmark = Bookmark::create($filter);
            $bookmark->BookmarkListID = $this->ID;
            $bookmark->SortOrder = $maxSort;
        }
        $bookmark->Title = $title;
        $bookmark->write();
        return $bookmark;
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
        return false;
    }

    public function canDelete($member = null)
    {
        return false; // Prevent deletion of bookmark lists directly
    }
}
