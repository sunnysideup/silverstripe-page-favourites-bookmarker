<?php

namespace Sunnysideup\PageFavouritesBookmarker\Api;

use SilverStripe\Control\Controller;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;

class BookmarkRequirements
{
    public static function require_page_specific_js(?Controller $controller = null, ?array $data = []): void
    {
        if (!$controller instanceof Controller) {
            $controller = Controller::curr();
        }

        $data['userIsLoggedIn'] = (bool) Security::getCurrentUser();
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
