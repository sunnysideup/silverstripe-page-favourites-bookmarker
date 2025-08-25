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
    public static function my_link(?string $action = null): string
    {
        $url = Config::inst()->get(static::class, 'url_segment');
        return Controller::join_links($url, $action);
    }

    private static string $url_segment = 'save-my-favourites';
    private static string $share_redirect_url = '/';

    private static string $backend_cookie_name = 'pf_store_code_backend';
    private static string $front_end_temporary_share_cookie_name = 'pf_store_share_bookmark_list';
    private static array $allowed_actions = [
        'events',
        'bookmarks',
        'share',
    ];



    protected ?BookmarkList $bookmarkList = null;

    public function init()
    {
        parent::init();
    }

    public function share($request = null)
    {
        // start list - and removing existing bookmarks
        if ($this->initSession()) {
            if ($this->bookmarkList === null) {
                return $this->sendResponse(['status' => 'error', 'message' => 'Bookmark list not initialized'], 500);
            }
            $this->bookmarkList->Bookmarks()->removeAll();

            // get items and the new ones
            $items = $request->param('ID');
            if (!$items) {
                return $this->httpError(404, 'No items provided');
            }
            $array = explode(',', $items);
            $this->bookmarkList->addManyByBookmarkUrlIds($array);
            $data = [];
            $data['code'] = $this->bookmarkList->Code;
            $data['numberOfBookmarks'] = $this->bookmarkList->Bookmarks()->count();
            $data['bookmarks'] = $this->bookmarkList->BookmarksAsArray();

            // render the share template
            return $this->renderWith(
                BookmarkController::class . '_share',
                [
                    'BookmarkListAsJson' => json_encode($data),
                    'BookmarkListCode' => $this->bookmarkList->Code,
                    'RedirectURL' => $this->config()->get('share_redirect_url'),
                    'NameOfTemporarySharedStore' =>  $this->config()->get('front_end_temporary_share_cookie_name')
                ]
            );
        }
        return $this->httpError(
            500,
            'Could not initialize session for sharing bookmarks',

        );
    }

    public function bookmarks($request = null)
    {
        $data = $this->getRequestJson();
        $code = CodeMaker::sanitize_code($data['code'] ?? '');
        $bookmarks = $data['bookmarks'] ?? [];
        $this->initSession($code);
        if ($this->bookmarkList) {
            foreach ($bookmarks as $bookmark) {
                $this->bookmarkList->addByUrlAndTitle(
                    $bookmark['url'] ?? '',
                    $bookmark['title'] ?? ''
                );
            }
            return $this->sendResponse([
                'bookmarks' => $this->bookmarkList->BookmarksAsArray(),
            ]);
        } else {
            return $this->sendResponse(['status' => 'error', 'message' => 'Bookmark list not found'], 404);
        }
    }


    public function events()
    {
        $data = $this->getRequestJson();
        $code = CodeMaker::sanitize_code($data['code'] ?? '');
        if ($this->initSession($code)) {
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
        }
        return $this->sendResponse(['status' => 'error', 'message' => 'Session initialization failed'], 500);
    }

    protected function addBookmark(?array $payload = null)
    {
        if ($payload) {
            $outcome = $this->bookmarkList->addByUrlAndTitle(
                $payload['url'],
                $payload['title']
            );
            if ($outcome) {
                return $this->sendResponse();
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
            return $this->sendResponse();
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
        return $this->sendResponse();
    }

    protected function updateListOfBookmarks()
    {
        // Logic to update the list of bookmarks
    }

    protected function initSession(?string $codeFromFrontEnd = ''): bool
    {
        $member = Security::getCurrentUser();
        $filter = [];
        if ($member) {
            $filter = [
                'MemberID' => $member->ID,
            ];
        } else {
            if (! $codeFromFrontEnd) {
                $codeCookieName = $this->config()->get('backend_cookie_name');
                $codeFromFrontEnd = Cookie::get($codeCookieName);
                if (! $codeFromFrontEnd) {
                    $codeFromFrontEnd = CodeMaker::make_alpha_num_code(12);
                    Cookie::set($codeCookieName, $codeFromFrontEnd, 99999, '/', null, true, true);
                }
            }
            $filter['Code'] = $codeFromFrontEnd;
        }
        $this->bookmarkList = BookmarkList::get()->filter($filter)->first();
        if (! $this->bookmarkList) {
            $this->bookmarkList = BookmarkList::create($filter);
        }
        $this->bookmarkList->write(false, false, true);
        return $this->bookmarkList->exists();
    }

    protected function sendResponse(?array $data = [], ?int $status = 200): HTTPResponse
    {
        if (!isset($data['status'])) {
            $data['status'] = 'success';
        }
        $data['numberOfBookmarks'] = $this->bookmarkList->Bookmarks()->count();
        $data['shareLink'] = $this->bookmarkList->ShareLink();
        $data['code'] = $this->bookmarkList->Code;
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
