<?php

namespace controllers;

use models\Admin;
use models\FriendRequest;
use models\User;
use models\GoogleUser;
use models\ChatMessage;

use traits\SecurityController;

class AdminController
{
    use SecurityController;

    private FriendRequest $friendrequest;
    private User $user;
    private GoogleUser $googleUser;
    private ChatMessage $chatmessage;
    private Admin $admin;

    public function __construct()
    {
        $this->friendrequest = new FriendRequest();
        $this -> user = new User();
        $this -> googleUser = new GoogleUser();
        $this->chatmessage = new ChatMessage();
        $this->admin = new Admin();
    }

    public function adminLandingPage(): void
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {


            $user = $this-> user -> getUserById($_SESSION['userId']);
            $usersOnline = $this-> admin -> countOnlineUsers();
            $purchases = $this-> admin -> countPurchases();
            $pendingReports = $this-> admin -> countPendingReports();
            $adminActions = $this-> admin -> getLastAdminActions();

            $current_url = "https://ur-sg.com/admin";
            $template = "views/admin/admin_landing";
            $page_title = "URSG - Admin";
            require "views/layoutAdmin.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminUpdateCurrency()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isAdmin()
        )
        {
            if (isset($_POST['currency']) && isset($_POST['user_id']))
            {
                $currency = $this->user->updateCurrency($_POST['user_id'], $_POST['currency']);

                if ($currency)
                {
                    $this-> admin -> logAdminAction($_SESSION['userId'],  $_POST['user_id'], "Updated Currency");
                    header("Location: /adminUsers?message=Money updated");
                    exit();
                }
                else
                {
                    header("Location: /adminUsers?message=Error updating money");
                    exit();
                }
            }
            else
            {
                header("Location: /adminUsers?message=Error updating money");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminCensorBio() 
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {
            if (isset($_POST['user_id']))
            {
                $censorBio = $this->admin->censorBio($_POST['user_id']);

                if ($censorBio)
                {
                    $this-> admin -> logAdminAction($_SESSION['userId'],  $_POST['user_id'], "Censor Bio");
                    header("Location: /adminUsers?message=Bio censored");
                    exit();
                }
                else
                {
                    header("Location: /adminUsers?message=Error censoring bio");
                    exit();
                }
            }
            else
            {
                header("Location: /adminUsers?message=Error censoring bio");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminCensorPicture() 
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {
            if (isset($_POST['user_id']))
            {
                $censorPicture = $this->admin->censorPicture($_POST['user_id']);

                if ($censorPicture)
                {
                    $this-> admin -> logAdminAction($_SESSION['userId'],  $_POST['user_id'], "Censor Picture");
                    header("Location: /adminUsers?message=Picture censored");
                    exit();
                }
                else
                {
                    header("Location: /adminUsers?message=Error censoring picture");
                    exit();
                }
            }
            else
            {
                header("Location: /adminUsers?message=Error censoring picture");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminUsersPage(): void
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {


            $user = $this-> user -> getUserById($_SESSION['userId']);
            $users = $this-> user -> getAllUsers();

            $current_url = "https://ur-sg.com/admin_users";
            $template = "views/admin/admin_users";
            $page_title = "URSG - Admin Users";
            require "views/layoutAdmin.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }
}
