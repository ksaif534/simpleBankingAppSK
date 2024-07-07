<?php

namespace App\classes;

class Cli{
    protected User $file;
    protected File $admin;
    protected $helpers;
    protected $errors;
    protected $adminArr;
    protected $adminCliPath = '../src/files/admin-cli.txt';

    public function __construct(User $file, File $admin, $helpers, $errors){
        $this->file     = $file;
        $this->admin    = $admin;
        $this->helpers  = $helpers;
        $this->errors   = $errors;
        $this->adminArr = array();
    }

    public function run() : int {
        global $argv;
        $commandName = $argv[1] ?? null;
        if ($commandName == 'create-admin') {
            while(true){
                $this->showOptions();
                $choice = $this->readChoice();
                switch ($choice) {
                    case 1:
                        $this->addName();
                        break;
                    case 2:
                        $this->addEmail();
                        break;
                    case 3:
                        $this->addPassword();
                        break;
                    case 4:
                        $this->submitForm();
                        break;
                    case 5:
                        $this->exitApp();
                        return 0;
                        break;
                    
                    default:
                        # code...
                        break;
                }
            }
        }else{
            $this->showHelp();
        }
    }

    private function showHelp() : void {
        echo "Usage: cli.php <command>\n";
        echo "Avaialble Commands: \n";
        echo " create-admin\n";
    }

    private function showOptions() : void {
        echo "Choose an Option: (Enter only the number of the option)\n";
        echo " 1. Enter Admin Name:\n";
        echo " 2. Enter Admin Email:\n";
        echo " 3. Enter Admin Password:\n";
        echo " 4. Submit Form\n";
        echo " 5. Exit\n";
        echo " Enter Your Choice:\n";
    }

    private function readChoice() : int {
        $handle = fopen("php://stdin","r");
        $line = fgets($handle);
        fclose($handle);
        return (int) trim($line);
    }

    private function readAdditionalInfo() : string {
        $handle = fopen("php://stdin","r");
        $line = fgets($handle);
        fclose($handle);
        return (string) trim($line);
    }

    private function exitApp() : void {
        echo "Exiting the application, goodbye!\n";
    }

    private function addName() : void {
        $name = $this->sanitizeName($this->readAdditionalInfo());
        if (filesize($this->admin->filename) > 0) {
            $this->adminArr = $this->admin->getData();
        }
        $this->adminArr['name'] = $name;
        $this->admin->putProcessedFileContent($this->adminCliPath,$this->adminArr);
    }

    private function sanitizeName($name) : string {
        if (empty($name)) {
            $this->errors['name'] = 'please provide a name';
            $this->helpers->flash('name',$this->errors['name']);
        }else{
            $name = $this->helpers->sanitize($name);
        }
        return $name;
    }

    private function addEmail() : void {
        $email = $this->sanitizeEmail($this->readAdditionalInfo());
        if (filesize($this->admin->filename) > 0) {
            $this->adminArr = $this->admin->getData();
        }
        $this->adminArr['email'] = $email;
        $this->admin->putProcessedFileContent($this->adminCliPath,$this->adminArr);
    }

    private function sanitizeEmail($email) : string {
        if (empty($email)) {
            $this->errros['email'] = 'please provide an email';
            $this->helpers->flash('email',$this->errors['email']);
        }else{
            $email = $this->helpers->sanitize($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->errors['email'] = 'please provide a valid email address';
                $this->helpers->flash('email',$this->errors['email']);
            }
        }
        return $email;
    }

    private function addPassword() : void {
        $password = $this->sanitizePassword($this->readAdditionalInfo());
        if (filesize($this->admin->filename) > 0) {
            $this->adminArr = $this->admin->getData();
        }
        $this->adminArr['password'] = $password;
        $this->admin->putProcessedFileContent($this->adminCliPath,$this->adminArr);
    }

    private function sanitizePassword($password) : string {
        if (empty($password)) {
            $this->errors['password'] = 'please provide a password';
            $this->helpers->flash('password',$this->errors['password']);
        }elseif (strlen($password) < 8) {
            $this->errors['password'] = 'password must be at least 8 characters';
            $this->helpers->flash('password',$this->errors['password']);
        }else{
            $password = $this->helpers->sanitize($password);
            $password = password_hash($password,PASSWORD_DEFAULT);
        }
        return $password;
    }

    private function addRole() : void {
        if (filesize($this->admin->filename) > 0) {
            $this->adminArr = $this->admin->getData();
        }
        $this->adminArr['role'] = 1;
        $this->admin->putProcessedFileContent($this->adminCliPath,$this->adminArr);
    }

    private function submitForm() : void {
        $this->addRole();
        $users = $this->file->getUsers();
        if (filesize($this->admin->filename) > 0) {
            $this->adminArr = $this->admin->getData();
        }
        $this->adminArr = $this->file->updatedFileInputWithAutoIncrement($users,$this->adminArr);
        array_push($users,$this->adminArr);
        $this->file->putProcessedFileContent('../src/files/users.txt',$users);
    }
}

?>