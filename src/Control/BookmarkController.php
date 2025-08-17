<?php

namespace Sunnysideup\PageFavouritesBookmarker\Control;

use SilverStripe\Control\Controller;

class BookmarkController extends Controller
{

    private static $allowed_actions = [
        'events',
        'bookmarks',
    ];
}
