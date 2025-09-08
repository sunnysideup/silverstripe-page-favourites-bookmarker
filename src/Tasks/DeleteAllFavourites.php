<?php


namespace Sunnysideup\PageFavouritesBookmarker\Tasks;

use SilverStripe\Dev\BuildTask;
use Sunnysideup\PageFavouritesBookmarker\Model\Bookmark;
use Sunnysideup\PageFavouritesBookmarker\Model\BookmarkList;
use Sunnysideup\PageFavouritesBookmarker\Model\BookmarkUrl;

class DeleteAllFavourites extends BuildTask
{
    protected $title = 'Delete all favourites';

    protected $description = 'Deletes all favourites - use with caution!';

    private static $segment = 'deleteallfavourites';

    public function run($request)
    {
        $classes = [
            Bookmark::class,
            BookmarkList::class,
            BookmarkUrl::class,
        ];
        foreach ($classes as $class) {
            $items = $class::get();
            $count = $items->count();
            if ($items && $count) {
                foreach ($items as $item) {
                    $item->delete();
                }
                echo 'Deleted ' . $count . ' items of class ' . $class . '<br />';
            } else {
                echo 'No items of class ' . $class . '<br />';
            }
        }
        echo 'done';
    }
}
