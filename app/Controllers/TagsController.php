<?php
namespace App\Controllers;

use App\Models\Tag;

class TagsController extends Controller{
    private Tag $tagObj;
    protected $db;

    public function __construct(){
        if(!AUTHGUARD()->isUserLoggedIn()){
            redirect('/login');
        }

        parent::__construct();
        $this->db = PDO();
        $this->tagObj = new Tag($this->db);
    }

    public function update(){
        $id = $_POST['id'] ?? 0;
        $data = [
            'name' => $_POST['name'] ?? ''
        ];

        // Find the tags directly associated with the user
        $targetTag = $this->tagObj->findById($id, AUTHGUARD()->user()->id);
        if(!$targetTag){
            redirect('/', [
                'flash_data' => ['error' => 'Tag not found or access denied.']
            ]);
        }
        $errors = $this->tagObj->validate($data, AUTHGUARD()->user()->id);

        if(empty($errors)){
            $targetTag->update($data['name']);
            redirect('/', [
                'flash_data' => ['success' => 'Tag updated successfully!']
            ]);
        } else {
            redirect('/', [
                'flash_data' => ['errors' => $errors, 'old' => $data]
            ]);
        }
    }

    public function delete(){
        $id = $_POST['id'] ?? 0;

        //Find the Tag directly by ID and ensure it belongs to the current user
        $targetTag = $this->tagObj->findById($id, AUTHGUARD()->user()->id);
        if(!$targetTag){
            redirect('/', [
                'flash_data' => ['error' => 'Tag not found or access denied.']
            ]);
        }

        $targetTag->delete();
        redirect('/', [
            'flash_data' => ['success' => 'Tag deleted successfully!']
        ]);
    }

    
}