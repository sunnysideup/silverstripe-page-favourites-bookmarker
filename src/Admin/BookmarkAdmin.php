<?php

namespace Sunnysideup\PageFavouritesBookmarker\Admin;

use SilverStripe\Admin\ModelAdmin;
use Sunnysideup\PageFavouritesBookmarker\Model\Bookmark;
use Sunnysideup\PageFavouritesBookmarker\Model\BookmarkList;

class BookmarkAdmin extends ModelAdmin
{

    private static $managed_models = [
        BookmarkList::class,
        Bookmark::class,
    ];

    private static $url_segment = 'bookmark-favourites';

    private static $menu_title = 'Favourites';
    private static $menu_icon_class = 'font-icon-circle-star';
}
