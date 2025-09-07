<?php

namespace Sunnysideup\PageFavouritesBookmarker\Api;

use SilverStripe\Control\Controller;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;

class BookmarkRequirements
{
    public static function require_page_specific_js(?Controller $controller = null, ?array $data = []): void
    {
        if (!$controller) {
            $controller = Controller::curr();
        }

        $data['userIsLoggedIn'] = Security::getCurrentUser() ? true : false;
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
