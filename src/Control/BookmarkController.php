<?php

namespace Sunnysideup\PageFavouritesBookmarker\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Security;
use Sunnysideup\PageFavouritesBookmarker\Api\CodeMaker;
use Sunnysideup\PageFavouritesBookmarker\Model\BookmarkList;

class BookmarkController extends Controller
{

    private static $url_segment = 'save-my-favourites';
    private static $allowed_actions = [
        'events',
        'bookmarks',
        'share',
    ];



    protected $bookmarkList;

    public function init()
    {
        parent::init();
    }

    public static function my_link(?string $action = null): string
    {
        $url = Config::inst()->get(static::class, 'url_segment');
        return Controller::join_links($url, $action);
    }

    public function load($request = null)
    {
        $this->initSession($request->getVar('ID'));
        if ($this->bookmarkList) {
            return $this->sendResponse([
                'status' => 'success',
                'bookmarks' => $this->bookmarkList->Bookmarks()->toArray(),
            ]);
        } else {
            return $this->sendResponse(['status' => 'error', 'message' => 'Bookmark list not found'], 404);
        }
    }

    public function share($request = null)
    {
        $this->initSession($request->getVar('ID'));
        $data = [];
        foreach ($this->bookmarkList->Bookmarks() as $bookmark) {
            $data[] = [
                'title' => $bookmark->Title,
                'url' => $bookmark->URL,
            ];
        }
        return $this->renderWith(
            BookmarkController::class . '_share',
            [
                'BookmarkList' => json_encode($data),
                'BookmarkListCode' => $this->bookmarkList->Code,
                'RedirectURL' => '/',
            ]
        );
    }

    public function events()
    {
        $this->initSession();
        $data = $this->getRequestJson();
        $payload = $data['payload'] ?? null;
        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'removed':
                    return $this->removeBookmark($payload);
                case 'added':
                    return $this->addBookmark($payload);
                case 'reordered':
                    return $this->resortBookmarks($payload);
                default:
                    return $this->sendResponse(['status' => 'error', 'message' => 'Unknown action'], 400);
            }
        }
        return $this->sendResponse(['status' => 'error', 'message' => 'No action specified'], 400);
        // Logic to handle events
    }

    protected function addBookmark(?array $payload = null)
    {
        if ($payload) {
            $outcome = $this->bookmarkList->addByUrlAndTitle(
                $payload['url'],
                $payload['title']
            );
            if ($outcome) {
                return $this->sendResponse(
                    ['status' => 'success',]
                );
            } else {
                return $this->sendResponse(
                    ['status' => 'error', 'message' => 'Failed to add bookmark'],
                    500
                );
            }
        }

        return $this->sendResponse(
            ['status' => 'error', 'message' => 'No data received for bookmark'],
            400
        );
    }

    protected function removeBookmark(?array $payload = null)
    {
        if ($payload) {
            $this->bookmarkList->removeByUrl($payload['url']);
            return $this->sendResponse(['status' => 'success',]);
        }

        return $this->sendResponse(['status' => 'error', 'message' => 'No data received for bookmark'], 400);
    }

    protected function resortBookmarks(?array $payload = null)
    {
        $from = $payload['from'] ?? -1;
        $to = $payload['to'] ?? -1;
        if ($from >= 0 && $to >= 0 && $from !== $to) {
            $fromObj = $this->bookmarkList->Bookmarks()->limit(1, $from)->first();
            $toObj = $this->bookmarkList->Bookmarks()->limit(1, $to)->first();
            if ($fromObj && $toObj) {
                $fromObj->SortOrder = $toObj->SortOrder;
                $fromObj->write();
                $toObj->SortOrder = $fromObj->SortOrder + 1;
                $toObj->write();
            }
            if ($from > $to) {
                // lets say from 12 to 4 then we need to move all objects from 5 to 11 up by one
                // the ones below 4 will not change
                // the ones above 12 will not change
                $objectsToResort = $this->bookmarkList->Bookmarks()
                    ->filter(['SortOrder:GreaterThan' => $to, 'SortOrder:LessThan' => $from])
                    ->exclude(['ID' => [$fromObj->ID, $toObj->ID]])
                    ->sort('SortOrder', 'ASC');
                foreach ($objectsToResort as $object) {
                    $object->SortOrder += 1;
                    $object->write();
                }
            } else {
                // lets say from 4 to 12 then we need to move all objects from 5 to 11 down by one
                // the ones below 4 will not change
                // the ones above 12 will not change
                $objectsToResort = $this->bookmarkList->Bookmarks()
                    ->filter(['SortOrder:GreaterThan' => $from, 'SortOrder:LessThan' => $to])
                    ->exclude(['ID' => [$fromObj->ID, $toObj->ID]])
                    ->sort('SortOrder', 'ASC');
                foreach ($objectsToResort as $object) {
                    $object->SortOrder -= 1;
                    $object->write();
                }
            }
        }
        return $this->sendResponse(['status' => 'success',]);
    }

    protected function updateListOfBookmarks()
    {
        // Logic to update the list of bookmarks
    }

    protected function initSession(?string $codeFromFrontEnd = ''): void
    {
        $member = Security::getCurrentUser();
        if ($member) {
            $filter = [
                'MemberID' => $member->ID,
            ];
        } else {
            $cookieCode = Cookie::get('pf_session_code');
            if (! $codeFromFrontEnd) {
                $codeFromFrontEnd = $cookieCode;
                // If no code is provided from the front end, generate a new one
                if (! $codeFromFrontEnd) {
                    $codeFromFrontEnd = CodeMaker::make_alpha_num_code(12);
                }
            }
            if ($cookieCode !== $codeFromFrontEnd) {
                // If the cookie code does not match the provided code, update the cookie
                Cookie::set('pf_session_code', $codeFromFrontEnd, 99999, '/', null, true, true);
            }
            $filter = [
                'Code' => $codeFromFrontEnd,
            ];
        }
        $bookmarkList = BookmarkList::get()->filter($filter)->first();
        if (! $bookmarkList) {
            $bookmarkList = BookmarkList::create($filter);
            $bookmarkList->write();
        }
        $this->bookmarkList = $bookmarkList;
    }

    protected function sendResponse(array $data, ?int $status = 200): HTTPResponse
    {
        $data['numberOfBookmarks'] = $this->bookmarkList->Bookmarks()->count();
        return HTTPResponse::create()
            ->setBody(json_encode($data))
            ->addHeader('Content-type', 'application/json')
            ->setStatusCode($status);
    }

    protected function getRequestJson(): array
    {
        $jsonString = $this->getRequest()->getBody();
        if (empty($jsonString)) {
            return [];
        }
        return json_decode($jsonString, true);
    }
}
