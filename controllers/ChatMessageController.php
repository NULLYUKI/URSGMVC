<?php

namespace controllers;

use models\ChatMessage;
use models\User;
use models\FriendRequest;
use models\LeagueOfLegends;
use models\UserLookingFor;
use models\GoogleUser;

use traits\SecurityController;


class ChatMessageController
{
    use SecurityController;

    private ChatMessage $chatmessage;
    private LeagueOfLegends $leagueoflegends;
    private UserLookingFor $userlookingfor;    
    private GoogleUser $googleUser;
    private User $user;
    private FriendRequest $friendrequest;
    private $senderId;
    private $receiverId;
    private $message;

    
    public function __construct()
    {
        $this -> chatmessage = new ChatMessage();
        $this -> leagueoflegends = new LeagueOfLegends();
        $this -> userlookingfor = new UserLookingFor();
        $this -> googleUser = new GoogleUser();
        $this -> user = new User();
        $this -> friendrequest = new FriendRequest();

    }

    public function pageChat()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf())
        {

            // Get important datas
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $usersAll = $this-> user -> getAllUsers();
            $unreadCount = $this-> chatmessage -> countMessage($_SESSION['userId']);
            $pendingCount = $this-> friendrequest -> countFriendRequest($_SESSION['userId']);
            $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);
            $lolUser = $this->leagueoflegends->getLeageUserByLolId($_SESSION['lol_id']);
            $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);
            $getFriendlist = $this-> friendrequest -> getFriendlist($_SESSION['userId']);

            $template = "views/swiping/swiping_chat";
            $page_title = "URSG - Chat";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: index.php");
            exit();
        }
    }

    public function pagePersoMessage()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf())
        {

            // Get important datas
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $usersAll = $this-> user -> getAllUsers();
            $unreadCount = $this-> chatmessage -> countMessage($_SESSION['userId']);
            $pendingCount = $this-> friendrequest -> countFriendRequest($_SESSION['userId']);
            $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);
            $lolUser = $this->leagueoflegends->getLeageUserByLolId($_SESSION['lol_id']);
            $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);

            if(isset($_GET['friend_id']))
            {
                $friendId = $_GET['friend_id'];
                $friend=$this->user->getUserById($friendId);
            }

            $template = "views/swiping/swiping_persomessage";
            $page_title = "URSG - Chat with " . $friend['user_username'];
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: index.php");
            exit();
        }
    }

    public function sendMessageData()
    {
        if (isset($_POST['param']))
        {
            $data = json_decode($_POST['param']);

            $status = "unread";
            
            $senderId = $data->senderId;
            $this->setSenderId($senderId);
            $receiverId = $data->receiverId;
            $this->setReceiverId($receiverId);
            $message = $data->message;
            $this->setMessage($message);

            $insertMessage = $this->chatmessage->insertMessage($this->getSenderId(), $this->getReceiverId(), $this->getMessage(), $status);
        }
    }

    public function getMessageData()
    {
        if (isset($_POST['userId']) && isset($_POST['friendId'])) {
            $userId = $_POST['userId'];
            $friendId = $_POST['friendId'];
    
            $messages = $this->chatmessage->getMessage($userId, $friendId);
            $friend = $this->user->getUserById($friendId);
            $user = $this->user->getUserById($userId);

            if($messages)
            {
                $status = "read";
                $updateStatus = $this->chatmessage->updateMessageStatus($status, $userId, $friendId);
            }
    
            if ($messages !== false && $friend !== false && $user !== false) {
                $data = [
                    'success' => true,
                    'friend' => [
                        'user_id' => $friend['user_id'],
                        'user_username' => $friend['user_username'],
                        'user_picture' => $friend['user_picture']
                    ],
                    'user' => [
                        'user_id' => $user['user_id'],
                        'user_username' => $user['user_username'],
                        'user_picture' => $user['user_picture']
                    ],
                    'messages' => $messages
                ];
    
                // Send JSON response
                echo json_encode($data);
            } else {
                echo json_encode(['success' => false, 'error' => 'No messages or user data found']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
    }

    public function getSenderId()
    {
        return $this->senderId;
    }

    public function setSenderId($senderId)
    {
        $this->senderId = $senderId;
    }

    public function getReceiverId()
    {
        return $this->receiverId;
    }

    public function setReceiverId($receiverId)
    {
        $this->receiverId = $receiverId;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

}
