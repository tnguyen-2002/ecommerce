<?php

namespace App\Controllers;

use App\Models\Url;
use App\Models\Tag;

class HomeController extends Controller
{
    protected $db;
    public function __construct()
    {
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login');
        }

        parent::__construct();
        $this->db = PDO();
    }

    public function index()
    {
        $shortUrl = new Url($this->db);
        $tag = new Tag($this->db);
        $userId = AUTHGUARD()->user()->id;

        $userUrls = $shortUrl->findByUser($userId);
        $userTags = $tag->findByUser($userId);

        //Handle tag filtering
        $selectedTags = [];
        if(isset($_GET['tags'])) {
            if (is_array($_GET['tags'])) {
                $selectedTags = $_GET['tags'];
            } else {
                $selectedTags = [$_GET['tags']];
            }
        }

        // Filter URLs based on selected tags
        if (!empty($selectedTags)) {
            $userUrls = array_filter($userUrls, function($url) use ($selectedTags) {
                $currentTagNames = $url->getTagNames();
                return empty($selectedTags) || !empty(array_intersect($selectedTags, $currentTagNames));
            });
        }

        $flashData = session_get_once('flash_data', []);
        $errors = $flashData['errors'] ?? [];
        $old = $flashData['old'] ?? [];

        $this->sendPage('index', [
            'urls' => $userUrls,
            'errors' => $errors,
            'old' => $old,
            'tags' => $userTags,
            'selectedTags' => $selectedTags
        ]);
    }
}
