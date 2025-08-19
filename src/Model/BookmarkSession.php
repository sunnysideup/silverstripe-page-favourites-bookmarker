<?php

namespace Sunnysideup\PageFavouritesBookmarker\Model;

use Page;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Sunnysideup\PageFavouritesBookmarker\Api\CodeMaker;

class BookmarkSession extends DataObject
{
    private static $table_name = 'BookmarkSession';

    private static $casting = [
        'Title' => 'Varchar',
        'Code' => 'Varchar',
        'Session' => 'Varchar',
    ];

    private static $has_one = [
        'Member' => Member::class,
    ];

    private static $has_many = [
        'Bookmarks' => Bookmark::class,
    ];

    public function getTitle()
    {
        $count = $this->Bookmarks()->count();
        if ($count === 0) {
            return 'Empty Session';
        }
        $s = $count > 1 ? 's' : '';
        if ($this->MemberID) {
            return $count . "Bookmark" . $s . ' for ' . $this->Member()->getName();
        }
        return $count . 'Anonymous Bookmark' . $s . ' ';
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->Code) {
            $this->Code = CodeMaker::make_alpha_num_code(10);
        }
        if (! $this->MemberID && Security::getCurrentUser()) {
            $this->MemberID = Security::getCurrentUser()->ID;
        }
    }
}
