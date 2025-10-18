<?php

namespace Sunnysideup\PageFavouritesBookmarker\Admin;

use Page;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
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

    private const DAYS_BACK_TRENDING = 7;

    public function getList(): ?\SilverStripe\ORM\DataList
    {
        $list = parent::getList();
        if ($this->modelClass === BookmarkList::class) {
            $include = Bookmark::get()->filter(['BookmarkListID:NOT' => [null, 0]])->columnUnique('BookmarkListID');
            $list = $list->filter(['ID' => $include]);
        } elseif ($this->modelClass === Bookmark::class) {
            $list = $list->sort(['ID' => 'DESC']);
        }
        return $list;
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        if ($this->modelClass === BookmarkList::class) {
            $ids = Bookmark::get()
                ->leftJoin('BookmarkUrl', '"BookmarkUrl"."ID" = "Bookmark"."BookmarkUrlID"')
                ->limit(300)
                ->filter(
                    [
                        'Created:GreaterThan' => date(
                            'Y-m-d',
                            strtotime('-' . self::DAYS_BACK_TRENDING . ' days')
                        ),
                        'BookmarkUrl.PageID:GreaterThan' => 0,
                    ]
                )
                ->column('BookmarkUrl.PageID');
            // count occurrences
            $counts = array_count_values($ids);

            // remove entries with fewer than 2
            $filtered = array_filter($counts, fn(int $count) => $count >= 2);

            // sort descending by count
            arsort($filtered, SORT_NUMERIC);

            // sort by count descending
            arsort($filtered);
            if (! empty($filtered)) {
                $list = Page::get()->filter(['ID' => array_keys($filtered)]);
                $sortStatement = ArrayMethods::create_sort_statement_from_id_array($filtered, Page::class, true);
                $list = $list->orderBy($sortStatement);

                $form->Fields()->unshift(
                    GridField::create(
                        'TrendingPages',
                        'Trending pages',
                        $list,
                        GridFieldConfig_RecordViewer::create()
                    )->setDescription('<br />Pages that have been added to favourites at least twice in the last ' . self::DAYS_BACK_TRENDING . ' days - most popular first.')
                        ->setForm($form)
                );
            }
        }
        return $form;
    }
}
