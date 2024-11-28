<?php

namespace controllers;

use models\Items;
use models\FriendRequest;
use models\User;
use models\GoogleUser;

use traits\SecurityController;

class ItemsController
{
    use SecurityController;

    private Items $items;
    private User $user;
    private GoogleUser $googleUser;

    public function __construct()
    {
        $this-> items = new Items();
        $this -> user = new User();
        $this -> googleUser = new GoogleUser();

    }

    public function pageStore()
    {

        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
        {

            // Get important datas
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $allUsers = $this-> user -> getAllUsers();
            $items = $this-> items -> getItems();

            
            // ARCANE EVENT
            $totalPiltoverCurrency = 0;
            $totalZaunCurrency = 0;

            foreach ($allUsers as $userArcane) {
                if ($userArcane['user_arcane'] === 'Piltover') {
                    $totalPiltoverCurrency += $userArcane['user_currency'];
                } elseif ($userArcane['user_arcane'] === 'Zaun') {
                    $totalZaunCurrency += $userArcane['user_currency'];
                }
            }

            $totalCurrency = $totalPiltoverCurrency + $totalZaunCurrency;
            $piltoverPercentage = $totalCurrency > 0 ? ($totalPiltoverCurrency / $totalCurrency) * 100 : 0;
            $zaunPercentage = 100 - $piltoverPercentage; 

            $current_url = "https://ur-sg.com/store";
            $template = "views/swiping/store";
            $page_title = "URSG - Store";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }
    public function getItems()
    {
        $response = array('message' => 'Error');
        if (isset($_POST['items'])) 
        {
            $items = $this-> items -> getItems();

            if ($items)
            {
                $response = array(
                    'items' => $items,
                    'message' => 'Success'
                );

                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            } else {
                $response = array('message' => 'Couldnt get all items');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            }

        } else {
            $response = array('message' => 'Cant access this');
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;  
        }
    }

    public function getOwnedItems()
    {
        $response = array('message' => 'Error');
        if (isset($_POST['userId'])) 
        {
            $items = $this-> items -> getOwnedItems($_POST['userId']);

            if ($items)
            {
                $response = array(
                    'items' => $items,
                    'message' => 'Success'
                );

                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            } else {
                $response = array('message' => 'Couldnt get all items');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            }

        } else {
            $response = array('message' => 'Cant access this');
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;  
        }
    }

    public function buyItem()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                $item = $this->items->getItemById($itemId);
                $user = $this->user->getUserById($userId);
                $ownedItems = $this->items->getOwnedItems($userId);

                if ($ownedItems) {
                    foreach ($ownedItems as $ownedItem) {
                        if ($item['items_id'] == $ownedItem['items_id']) {
                            echo json_encode(['success' => false, 'message' => 'Item already owned']);
                            return;
                        }
                    }
                }

                if ($item && $user) {
                    if ($user['user_currency'] >= $item['items_price']) {
                        $this->items->buyItem($itemId, $userId);
                        $price = $item['items_price'];
                        if ($user['user_isVip'] == 1) {
                            $price = $item['items_price'] * 0.8;
                        } 
                        $this->user->updateCurrency($userId, $user['user_currency'] - $price);
                        echo json_encode(['success' => true, 'message' => 'Item bought successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Not enough currency']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }


        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function buyItemPhone()
    {
        // Validate Authorization Header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
        // Check if 'param' is set in POST data
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
    
            // Check if required fields 'itemId' and 'userId' are set
            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;
    
                // Validate token for the user
                if (!$this->validateToken($token, $userId)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid token']);
                    return;
                }
    
                $item = $this->items->getItemById($itemId);
                $user = $this->user->getUserById($userId);
                $ownedItems = $this->items->getOwnedItems($userId);
    
                // Check if the user already owns the item
                if ($ownedItems) {
                    foreach ($ownedItems as $ownedItem) {
                        if ($item['items_id'] == $ownedItem['items_id']) {
                            echo json_encode(['success' => false, 'message' => 'Item already owned']);
                            return;
                        }
                    }
                }
    
                // Check if the item and user exist
                if ($item && $user) {
                    // Check if the user has enough currency
                    if ($user['user_currency'] >= $item['items_price']) {
                        $this->items->buyItem($itemId, $userId);
                        $price = $item['items_price'];
    
                        // Apply discount for VIP users
                        if ($user['user_isVip'] == 1) {
                            $price = $item['items_price'] * 0.8;
                        } 
    
                        // Update user's currency after purchase
                        $this->user->updateCurrency($userId, $user['user_currency'] - $price);
                        echo json_encode(['success' => true, 'message' => 'Item bought successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Not enough currency']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }
    
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }
    

    public function buyItemWebsite()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                    $user = $this->user->getUserById($_SESSION['userId']);
                    if (isset($_SESSION)) {
    
                        if ($user['user_id'] != $userId)
                        {
                            echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                            return;
                        }
                    }

                $item = $this->items->getItemById($itemId);
                $user = $this->user->getUserById($userId);
                $ownedItems = $this->items->getOwnedItems($userId);

                if ($ownedItems) {
                    foreach ($ownedItems as $ownedItem) {
                        if ($item['items_id'] == $ownedItem['items_id']) {
                            echo json_encode(['success' => false, 'message' => 'Item already owned']);
                            return;
                        }
                    }
                }

                if ($item && $user) {
                    if ($user['user_currency'] >= $item['items_price']) {
                        $this->items->buyItem($itemId, $userId);
                        $price = $item['items_price'];
                        if ($user['user_isVip'] == 1) {
                            $price = $item['items_price'] * 0.8;
                        } 
                        $this->user->updateCurrency($userId, $user['user_currency'] - $price);
                        echo json_encode(['success' => true, 'message' => 'Item bought successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Not enough currency']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }


        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }


    public function buyRole()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                $item = $this->items->getItemById($itemId);
                $user = $this->user->getUserById($userId);
                $ownedItems = $this->items->getOwnedItems($userId);

                if ($ownedItems) {
                    foreach ($ownedItems as $ownedItem) {
                        if ($item['items_id'] == $ownedItem['items_id']) {
                            echo json_encode(['success' => false, 'message' => 'Role already owned']);
                            return;
                        }
                    }
                }

                if ($item && $user) {
                    if ($user['user_currency'] >= $item['items_price']) {
                        $this->items->buyItem($itemId, $userId);
                        $this->user->buyPremium($userId);
                        $price = $item['items_price'];
                        if ($user['user_isVip'] == 1) {
                            $price = $item['items_price'] * 0.8;
                        } 
                        $this->user->updateCurrency($userId, $user['user_currency'] - $price);
                        echo json_encode(['success' => true, 'message' => 'Role bought successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Not enough currency']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }


        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function buyRolePhone()
    {
        // Validate Authorization Header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
        // Check if 'param' is set in POST data
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
    
            // Check if required fields 'itemId' and 'userId' are set
            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;
    
                // Validate token for the user
                if (!$this->validateToken($token, $userId)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid token']);
                    return;
                }
    
                $item = $this->items->getItemById($itemId);
                $user = $this->user->getUserById($userId);
                $ownedItems = $this->items->getOwnedItems($userId);
    
                // Check if the user already owns the item
                if ($ownedItems) {
                    foreach ($ownedItems as $ownedItem) {
                        if ($item['items_id'] == $ownedItem['items_id']) {
                            echo json_encode(['success' => false, 'message' => 'Role already owned']);
                            return;
                        }
                    }
                }
    
                // Check if the item and user exist
                if ($item && $user) {
                    // Check if the user has enough currency
                    if ($user['user_currency'] >= $item['items_price']) {
                        $this->items->buyItem($itemId, $userId);
                        $this->user->buyPremium($userId);
                        $price = $item['items_price'];
    
                        // Apply discount for VIP users
                        if ($user['user_isVip'] == 1) {
                            $price = $item['items_price'] * 0.8;
                        } 
    
                        // Update user's currency after purchase
                        $this->user->updateCurrency($userId, $user['user_currency'] - $price);
                        echo json_encode(['success' => true, 'message' => 'Role bought successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Not enough currency']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }
    
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }    

    public function buyRoleWebsite()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                    $user = $this->user->getUserById($_SESSION['userId']);
                    if (isset($_SESSION)) {
    
                        if ($user['user_id'] != $userId)
                        {
                            echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                            return;
                        }
                    }

                $item = $this->items->getItemById($itemId);
                $user = $this->user->getUserById($userId);
                $ownedItems = $this->items->getOwnedItems($userId);

                if ($ownedItems) {
                    foreach ($ownedItems as $ownedItem) {
                        if ($item['items_id'] == $ownedItem['items_id']) {
                            echo json_encode(['success' => false, 'message' => 'Role already owned']);
                            return;
                        }
                    }
                }

                if ($item && $user) {
                    if ($user['user_currency'] >= $item['items_price']) {
                        $this->items->buyItem($itemId, $userId);
                        $this->user->buyPremium($userId);
                        $price = $item['items_price'];
                        if ($user['user_isVip'] == 1) {
                            $price = $item['items_price'] * 0.8;
                        } 
                        $this->user->updateCurrency($userId, $user['user_currency'] - $price);
                        echo json_encode(['success' => true, 'message' => 'Role bought successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Not enough currency']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }


        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function usePictureFrame()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                $ownedItems = $this->items->getOwnedItems($userId);

                if ($ownedItems) {
                    foreach ($ownedItems as $ownedItem) {
                        if ($ownedItem['items_category'] == 'profile Picture') {
                            $this->items->removeItems($ownedItem['userItems_id'], $userId);
                        }
                    }
                } 

                if ($itemId && $userId) {
                    $useItems = $this->items->useItems($itemId, $userId);

                    if ($useItems) {
                        echo json_encode(['success' => true, 'message' => 'Frame used successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Frame not used']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }


        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function usePictureFrameWebsite()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                    $user = $this->user->getUserById($_SESSION['userId']);
                    if (isset($_SESSION)) {
    
                        if ($user['user_id'] != $userId)
                        {
                            echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                            return;
                        }
                    }

                $ownedItems = $this->items->getOwnedItems($userId);

                if ($ownedItems) {
                    foreach ($ownedItems as $ownedItem) {
                        if ($ownedItem['items_category'] == 'profile Picture') {
                            $this->items->removeItems($ownedItem['userItems_id'], $userId);
                        }
                    }
                } 

                if ($itemId && $userId) {
                    $useItems = $this->items->useItems($itemId, $userId);

                    if ($useItems) {
                        echo json_encode(['success' => true, 'message' => 'Frame used successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Frame not used']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }


        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function removePictureFrame()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                if ($itemId && $userId) {
                    $removeItems = $this->items->removeItems($itemId, $userId);

                    if ($removeItems) {
                        echo json_encode(['success' => true, 'message' => 'Frame removed successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Frame not removed']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }


        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function removePictureFrameWebsite()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                    $user = $this->user->getUserById($_SESSION['userId']);
                    if (isset($_SESSION)) {
    
                        if ($user['user_id'] != $userId)
                        {
                            echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                            return;
                        }
                    }

                if ($itemId && $userId) {
                    $removeItems = $this->items->removeItems($itemId, $userId);

                    if ($removeItems) {
                        echo json_encode(['success' => true, 'message' => 'Frame removed successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Frame not removed']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }


        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function validateToken($token, $userId): bool
    {
        $storedTokenData = $this->googleUser->getMasterTokenByUserId($userId);
    
        if ($storedTokenData && isset($storedTokenData['google_masterToken'])) {
            $storedToken = $storedTokenData['google_masterToken'];
            return hash_equals($storedToken, $token);
        }
    
        return false;
    }
}
