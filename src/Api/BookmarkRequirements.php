<?php

namespace Sunnysideup\PageFavouritesBookmarker\Api;

use SilverStripe\Control\Controller;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;

class BookmarkRequirements
{
    public static function js(): void
    {
        $controller = Controller::curr();
        $loadOnThisPage = $controller->hasMethod('PageFavouritesBookmarkerLoadOnThisPage')
            ? $controller->PageFavouritesBookmarkerLoadOnThisPage()
            : true;
        $data = ['userIsLoggedIn' => Security::getCurrentUser() ? true : false];
        if ($loadOnThisPage === false) {
            $data['loadOnThisPage'] = false;
        }
        if ($controller->hasMethod('PageFavouritesBookmarkerMoreRequirementsData')) {
            $data += $controller->PageFavouritesBookmarkerMoreRequirementsData();
        }
        Requirements::customScript(
            "
            window.pageFavouritesBookmarker = window.pageFavouritesBookmarker || {};
            window.pageFavouritesBookmarker = " . json_encode($data) . ";"
        );
    }
}
