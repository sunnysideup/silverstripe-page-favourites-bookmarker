<?php

namespace Sunnysideup\PageFavouritesBookmarker\Api;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;

class BookmarkRequirements
{
    use Injectable;
    use Configurable;

    private static bool $show_user_specific_js = false;

    public static function require_page_specific_js(?Controller $controller = null, ?array $data = []): void
    {
        if (!$controller) {
            $controller = Controller::curr();
        }
        if (Config::inst()->get(BookmarkRequirements::class, 'show_user_specific_js')) {
            $data['userIsLoggedIn'] = Security::getCurrentUser() ? true : false;
        }
        if ($controller->hasMethod('PageFavouritesBookmarkerMoreRequirementsData')) {
            $data += $controller->PageFavouritesBookmarkerMoreRequirementsData();
        }
        Requirements::customScript(
            "
            window.npmPageFavouritesBookmarkerConfig = " . json_encode($data) . ";",
            'npmPageFavouritesBookmarkerConfig'
        );
    }
}
