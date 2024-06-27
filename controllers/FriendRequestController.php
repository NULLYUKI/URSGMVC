<?php

namespace controllers;

use models\FriendRequest;
use models\User;
use models\ChatMessage;
use traits\SecurityController;

class FriendRequestController
{
    use SecurityController;

    private FriendRequest $friendrequest;
    private User $user;
    private ChatMessage $chatmessage;

    
    public function __construct()
    {
        $this -> friendrequest = new FriendRequest();
        $this -> user = new User();
        $this -> chatmessage = new ChatMessage();
    }

}
