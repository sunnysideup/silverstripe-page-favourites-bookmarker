<?php

namespace Sunnysideup\PageFavouritesBookmarker\Admin;

use SilverStripe\Admin\ModelAdmin;
use Sunnysideup\PageFavouritesBookmarker\Model\Bookmark;
use Sunnysideup\PageFavouritesBookmarker\Model\BookmarkList;
use Sunnysideup\PageFavouritesBookmarker\Model\BookmarkUrl;

class BookmarkAdmin extends ModelAdmin
{

    private static $managed_models = [
        BookmarkList::class,
        Bookmark::class,
        BookmarkUrl::class,
    ];

    private static $url_segment = 'bookmark-favourites';

    private static $menu_title = 'Favourites';
    private static $menu_icon_class = 'font-icon-circle-star';

    public function getList(): ?\SilverStripe\ORM\DataList
    {
        $list = parent::getList();
        if ($this->modelClass === BookmarkList::class) {
            $include = Bookmark::get()->filter(['BookmarkListID:NOT' => [null, 0]])->columnUnique('BookmarkListID');
            $list = $list->filter(['ID' => $include]);
        }
        return $list;
    }
}
