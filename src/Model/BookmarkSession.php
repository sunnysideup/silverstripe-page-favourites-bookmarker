<?php

namespace Sunnysideup\PageFavouritesBookmarker\Model;

use Page;

class BookmarkSession extends DataObject
{
    private static $table_name = 'BookmarkSession';

    private static $casting = [
        'Title' => 'Varchar',
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
}
