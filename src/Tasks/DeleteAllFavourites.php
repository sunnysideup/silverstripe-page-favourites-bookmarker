<?php


namespace Sunnysideup\PageFavouritesBookmarker\Tasks;

use Symfony\Component\Console\Input\InputInterface;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use SilverStripe\Dev\BuildTask;
use Sunnysideup\PageFavouritesBookmarker\Model\Bookmark;
use Sunnysideup\PageFavouritesBookmarker\Model\BookmarkList;
use Sunnysideup\PageFavouritesBookmarker\Model\BookmarkUrl;

class DeleteAllFavourites extends BuildTask
{
    protected string $title = 'Delete all favourites';

    protected static string $description = 'Deletes all favourites - use with caution!';

    protected static string $commandName = 'deleteallfavourites';

    protected function execute(InputInterface $input, PolyOutput $output): int
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

        $output->writeln('done');
        return Command::SUCCESS;
    }
}
