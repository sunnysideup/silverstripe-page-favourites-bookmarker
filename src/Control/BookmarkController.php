<?php

namespace Sunnysideup\PageFavouritesBookmarker\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Session;
use SilverStripe\Security\Security;
use Sunnysideup\PageFavouritesBookmarker\Api\CodeMaker;
use Sunnysideup\PageFavouritesBookmarker\Model\BookmarkSession;

class BookmarkController extends Controller
{

    private static $allowed_actions = [
        'events',
        'bookmarks',
    ];

    protected function addBookmark()
    {
        $session = $this->initSession($codeFromFrontEnd ?? '');
        // Logic to add a bookmark
    }

    protected function removeBookmark()
    {
        // Logic to remove a bookmark
    }

    protected function updateListOfBookmarks()
    {
        // Logic to update the list of bookmarks
    }

    protected function initSession(?string $codeFromFrontEnd = '')
    {
        $member = Security::getCurrentUser();
        if ($member) {
            $filter = [
                'MemberID' => $member->ID,
            ];
        } else {
            if (! $codeFromFrontEnd) {
                $codeFromFrontEnd = CodeMaker::make_alpha_num_code(10);
            }
            Cookie::set('pf_session_code', $codeFromFrontEnd, 99999, '/', null, true, true);
            $filter = [
                'Code' => $codeFromFrontEnd,
            ];
        }
        $session = BookmarkSession::get()->filter($filter)->first();
        if (! $session) {
            $session = BookmarkSession::create($filter);
            $session->write();
        }
        return $session;
    }
}
