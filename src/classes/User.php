<?php

namespace App\classes;

use App\classes\Helpers;

class User{
    protected File $file;
    protected $helpers;

    public function __construct(File $file){
        $this->file = $file;
    }

    public function getUsers() : array
    {
        return $this->file->getData();
    }

    public function getAllRegisteredCustomers(): array
    {
        $allUsers   = $this->getUsers();
        $query      = [];
        foreach ($allUsers as $user) {
            if ($user['role'] == 2) {
                array_push($query, $user);
            }
        }
        return $query;
    }

    public function getAuthenticatedUserBySession(){
        $this->helpers = new Helpers();
        $query = [];
        if ($this->helpers->checkSession()) {
            foreach ($this->getUsers() as $user) {
                if ($user['id'] == $_SESSION['user_id']) {
                    $query = $user;
                }
            }
        }
        return $query;
    }

    public function getFileName() : string
    {
        return $this->file->filename;
    }

    public function getProcessedFileContent($filename){
        return $this->file->getProcessedFileContent($filename);
    }

    public function putProcessedFileContent($filename,$data){
        return $this->file->putProcessedFileContent($filename,$data);
    }

    public function getUserByEmail($filename,$email){
        $unserializedFileContent = $this->getProcessedFileContent($filename);
        $query = [];
        foreach ($unserializedFileContent as $user) {
            if ($user['email'] == $email) {
                $query = $user;
                break;
            }
        }
        return $query;
    }

    public function updatedFileInputWithAutoIncrement($users,$user){
        $max_id = 0;
        foreach ($users as $item) {
            if ($item['id'] > $max_id) {
                $max_id = $item['id'];
            }
        }
        $new_id = $max_id + 1;
        $updatedUser = [
            'id'        => $new_id,
            'name'      => $user['name'],
            'email'     => $user['email'],
            'password'  => $user['password'],
            'role'      => $user['role']
        ];
        return $updatedUser;
    }
}

?>