<?php
namespace App\Controllers;

use App\Models\Url;

class ShortUrlsController extends Controller{
    private Url $shortUrl;
    protected $db;

    public function __construct(){
        if(!AUTHGUARD()->isUserLoggedIn()){
            redirect('/login');
        }

        parent::__construct();
        $this->db = PDO();
        $this->shortUrl = new Url($this->db);
    }

    public function store(){
        $data = [
            'longUrl' => $_POST['longUrl'] ?? '',
            'userId' => AUTHGUARD()->user()->id,
            'tags' => $this->parseTags($_POST['tags'] ?? '')
        ];

        $errors = $this->shortUrl->validate($data);

        if(empty($errors)){
            $this->shortUrl->create($data);
            redirect('/', [
            'flash_data' => ['success' => 'Short URL created successfully!']
        ]);
        } else {
            redirect('/',[
                'flash_data' => ['errors' => $errors, 'old' => $data]
            ]);
        }
    }

    private function parseTags(string $tagsString): array{
        if(empty($tagsString)) {
            return [];
        }

        //Split by comma and clean up
        $tags = array_map('trim', explode(',', $tagsString));
        $tags = array_filter($tags, function($tag){
            return !empty($tag);
        });

        return array_values($tags);
    }

    public function update(){
        $id = $_POST['id'] ?? 0;
        $data = [
            'longUrl' => $_POST['longUrl'] ?? '',
            'tags' => $this->parseTags($_POST['tags'] ?? '')
        ];

        //Find the URL directly by ID and ensure it belongs to the current user
        $targetUrl = $this->shortUrl->findById($id, AUTHGUARD()->user()->id);
        if(!$targetUrl){
            redirect('/', [
                'flash_data' => ['error' => 'Short URL not found or access denied.']
            ]);
        }

        $errors = $this->shortUrl->validate($data);

        if(empty($errors)){
            $targetUrl->update($data);
            redirect('/', [
                'flash_data' => ['success' => 'Short URL updated successfully!']
            ]);
        } else {
            redirect('/', [
                'flash_data' => ['errors' => $errors, 'old' => $data]
            ]);
        }
    }

    public function delete(){
        $id = $_POST['id'] ?? 0;

        //Find the URL directly by ID and ensure it belongs to the current user
        $targetUrl = $this->shortUrl->findById($id, AUTHGUARD()->user()->id);
        if(!$targetUrl){
            redirect('/', [
                'flash_data' => ['error' => 'Short URL not found or access denied.']
            ]);
        }

        $targetUrl->delete();
        redirect('/', [
            'flash_data' => ['success' => 'Short URL deleted successfully!']
        ]);
    }

    public function redirect($slug){
        $shortUrl = $this->shortUrl->findBySlug($slug);

        if(!$shortUrl){
            http_response_code(404);
            $this->sendPage('errors/404');
        }

        //Redirect to the long URL
        redirect($shortUrl->longUrl);
    }

    
}