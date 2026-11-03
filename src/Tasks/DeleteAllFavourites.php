<?php

namespace Sunnysideup\PageFavouritesBookmarker\Tasks;

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use Sunnysideup\PageFavouritesBookmarker\Model\Bookmark;
use Sunnysideup\PageFavouritesBookmarker\Model\BookmarkList;
use Sunnysideup\PageFavouritesBookmarker\Model\BookmarkUrl;

class DeleteAllFavourites extends BuildTask
{
    protected $title = 'Delete all favourites';

    protected $description = 'Deletes all favourites - use with caution! Shows what would be deleted unless confirm=1 is added.';

    private static $segment = 'deleteallfavourites';

    public function run($request)
    {
        $confirmed = (bool) $request->requestVar('confirm');
        if ($confirmed) {
            if (! Director::is_cli()) {
                echo 'This task can only be run on the command line.';

                return;
            }
        } else {
            echo 'DRY RUN: nothing is deleted. Add ?confirm=1 to really delete all favourites.<br />';
        }

        $classes = [
            Bookmark::class,
            BookmarkList::class,
            BookmarkUrl::class,
        ];
        foreach ($classes as $class) {
            $items = $class::get();
            $count = $items->count();
            if ($items && $count) {
                if ($confirmed) {
                    foreach ($items as $item) {
                        $item->delete();
                    }
                    echo 'Deleted ' . $count . ' items of class ' . $class . '<br />';
                } else {
                    echo 'Would delete ' . $count . ' items of class ' . $class . '<br />';
                }
            } else {
                echo 'No items of class ' . $class . '<br />';
            }
        }
        echo 'done';
    }
}
