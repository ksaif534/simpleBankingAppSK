<?php

namespace App\classes;

class Transaction{
    protected File $file;
    public $user;
    public $authUser;
    protected $errors;
    protected $helpers;
    public $amount;

    public function __construct(File $file,User $user, $errors, $helpers){
        $this->file         = $file;
        $this->user         = $user;
        $this->errors       = $errors;
        $this->helpers      = $helpers;
        $this->authUser     = $this->getAuthenticatedUserBySession();
    }

    public function getAllTransactions() : array
    {
        return $this->file->getData();
    }

    public function getTransactionsByUser($requestUri) : array
    {
        $wildcard       = $this->fetchWildCard($requestUri);
        $userId         = 0;
        $email          = ''; 
        $wildCardArr    = $this->fetchWildCardArr($wildcard);
        $userId         = $this->fetchUserId($wildCardArr);
        $email          = $this->fetchEmail($wildCardArr);
        $query = [];
        $transactions = $this->getAllTransactions();
        foreach ($transactions as $transaction) {
            if ($transaction['user_id'] == $userId || ($transaction['receiver_email'] == $email && $transaction['type'] == 3)) {
                array_push($query,$transaction);
            }
        }
        return $query;
    }

    public function fetchWildCard($requestUri) : string
    {
        $wildcard           = null;
        if (strpos($requestUri,'.php') !== false) {
            $position = strpos($requestUri,'.php') + 4;
            $wildcard = substr($requestUri,$position);
        }
        return $wildcard;
    }

    public function fetchWildCardArr($wildcard) : array
    {
        return explode("/",$wildcard);
    }

    public function fetchUserId($wildCardArr){
        return $wildCardArr[1];
    }

    public function fetchEmail($wildCardArr) : string
    {
        return $wildCardArr[2];
    }

    public function getHelpers(){
        return $this->helpers;
    }

    public function getAuthenticatedUserBySession() : array
    {
        return $this->user->getAuthenticatedUserBySession();
    }

    public function getAllUserSpecificTransactions() : array
    {
        $query          = [];
        foreach ($this->getAllTransactions() as $transaction) {
            if ($transaction['user_id'] == $this->authUser['id'] || ($transaction['receiver_email'] == $this->authUser['email'] && $transaction['type'] == 3)) {
                array_push($query,$transaction);
            }
        }
        return $query;
    }

    public function calculateCurrentUserBalance() : int
    {
        $sum = 0;
        foreach ($this->getAllTransactions() as $transaction) {
            if ($transaction['user_id'] != $this->authUser['id']) {
                //If User Receives Transfer
                if ($transaction['receiver_email'] == $this->authUser['email'] && $transaction['type'] == 3) {
                    $sum += $transaction['amount'] * -1;
                }
            }else{
                $sum += $transaction['amount'];
            }
        }
        return $sum;
    }

    public function getFileName() : string
    {
        return $this->file->filename;
    }

    public function storeTransaction() : void
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->sanitizeAmount();
            if (empty($this->errors)) {
                if ($_POST['type'] == 1) {
                    $this->store();
                }else{
                    if ($this->isWithdrawable($this->amount)) {
                        $this->store();
                    }else{
                        if ($this->isTransferrable($this->amount)) {
                            $this->store();
                        }else{
                            $this->errors['transfer_error'] = 'Sorry, not enough current balance to transfer';
                            $this->helpers->flash('transfer_error',$this->errors['transfer_error']);
                        }
                        $this->errors['withdraw_error'] = 'Sorry, not enough current balance to withdraw';
                        $this->helpers->flash('withdraw_error',$this->errors['withdraw_error']);
                    }
                }
            }
        }
    }

    public function store() : void 
    {
        $authUser       = $this->user->getAuthenticatedUserBySession();
        $email          = isset($_POST['type']) ? ($_POST['type'] != 3 ? $authUser['email'] : $_POST['email']) : '';
        $userByEmail    = $this->user->getUserByEmail($this->user->getFileName(),$email);
        $sender         = $this->authUser['name'];
        $receiver       = isset($_POST['type']) ? ($_POST['type'] != 3 ? $authUser['name'] : $userByEmail['name']) : '';
        $amountSignature= isset($_POST['type']) ? ($_POST['type'] == 1 ? 1 : -1) : 0; 
        $transaction = [
            'user_id'           => $_SESSION['user_id'],
            'sender_name'       => $sender,
            'receiver_email'    => $email,
            'receiver_name'     => $receiver,
            'type'              => $_POST['type'],
            'amount'            => $this->amount * $amountSignature,
            'date'              => date('Y-m-d H:i:s')
        ];
        $transactions   = $this->getAllTransactions();
        array_push($transactions,$transaction);
        if ($this->putProcessedFileContent($this->getFileName(),$transactions)) {
            switch ($_POST['type']) {
                case 1:
                    //Deposit
                    $this->helpers->flash('success', 'You have successfully deposited the transaction amount');
                    header('Location: dashboard.php');
                    exit;
                    break;
                case 2:
                    //Withdraw
                    $this->helpers->flash('success', 'You have successfully withdrawn the transaction amount');
                    header('Location: dashboard.php');
                    exit;
                    break;
                case 3:
                    //Transfer
                    $this->helpers->flash('success', 'You have successfully transferred the transaction amount');
                    header('Location: dashboard.php');
                    exit;
                    break;
                
                default:
                    # code...
                    break;
            }
        }else{
            $this->errors['amount_error'] = 'A Transaction Error Occured. Please Try Again.';
            $this->helpers->flash('amount_error',$this->errors['amount_error']);
        }
    }

    public function putProcessedFileContent($filename, $data){
        return $this->file->putProcessedFileContent($filename, $data);
    }

    public function sanitizeAmount() : void
    {
        if (empty($_POST['amount'])) {
            $this->errors['amount'] = 'Please provide a valid amount';
        }else{
            $this->amount = $this->helpers->sanitize($_POST['amount']);
        }
    }

    public function isWithdrawable($amountToWithdraw) : bool
    {
        $currentUserBalance = $this->calculateCurrentUserBalance();
        if ($amountToWithdraw > $currentUserBalance) {
            return false;
        }
        return true;
    }

    public function isTransferrable($amountToTransfer) : bool
    {
        $currentUserBalance = $this->calculateCurrentUserBalance();
        if ($amountToTransfer > $currentUserBalance) {
            return false;
        }
        return true;
    }
}

?>