let userId = document.getElementById("senderId").value;
let friendId = document.getElementById("receiverId").value;
let currentMessages = []; // Store the current messages
let isFirstFetch = true; // Flag to track the first fetch
let friendData = document.getElementById('friendInfo');
let chatInterface = document.querySelector('.chat-interface');



document.addEventListener("DOMContentLoaded", function() {

    // Initially fetch messages
    fetchMessages(userId, friendId);

    // Optionally, you can set an interval to fetch messages periodically
    setInterval(() => fetchMessages(userId, friendId), 5000); // Fetch messages every 5 seconds

    // Set the variable initially
    setVhVariable();
    
    // Update the variable on resize
    window.addEventListener('resize', setVhVariable);

    // Initial check
    checkScreenSize();

    // Add event listener for screen resize
    window.addEventListener('resize', checkScreenSize);

});


    // Show loading indicator
    function showLoadingIndicator() {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.innerHTML = '<p>Loading messages...</p>';
    }

    // Function to fetch messages
    function fetchMessages(userId, friendId) {
        if (isFirstFetch) {
            showLoadingIndicator();
            isFirstFetch = false; // Reset the flag after the first fetch
        }

        console.log('Fetching messages for userId:', userId, 'and friendId:', friendId);

        fetch('index.php?action=getMessageData', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `userId=${encodeURIComponent(userId)}&friendId=${encodeURIComponent(friendId)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Compare the fetched messages with the current messages
                if (data.messages !== null && data.messages !== undefined) {
                    console.log('Messages fetched successfully:', data.messages);
                    if (JSON.stringify(currentMessages) !== JSON.stringify(data.messages)) {
                        currentMessages = data.messages; // Update the current messages
                        updateMessageContainer(data.messages, data.friend, data.user);
                    } else {
                        console.log('No new messages. No update needed.');
                    }
                }
                else
                {
                    showFriendInfo(data.friend);
                }
            } else {
                console.error('Error fetching messages:', data.error);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);

            // Retry fetching messages after a delay
            setTimeout(() => fetchMessages(userId, friendId), 5000);
        });
    }

    // Function to update message container
    function updateMessageContainer(messages, friend, user) {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.innerHTML = ''; // Clear current messages
        console.log('Updating message container with messages:', messages);

        messages.forEach(message => {
            let isCurrentUser = (message.chat_senderId == userId);
            let messageClass = isCurrentUser ? 'message-from-user' : 'message-to-user';
            let messagePosition = isCurrentUser ? 'right' : 'left';
            let messageUser = isCurrentUser ? user : friend;
            let messageLink = isCurrentUser ? 'userProfile' : 'anotherUser';
            let pictureLink;

            let messageDiv = document.createElement("div");
            messageDiv.classList.add("message", messageClass);
            messageDiv.style.textAlign = messagePosition;

            if (messageUser.user_picture === null || messageUser.user_picture === undefined) {
                pictureLink = "images/defaultprofilepicture.jpg";
            } else {
                pictureLink = `upload/${messageUser.user_picture}`;
            }

            // Create message content
            let messageContent = `
                <p id="username_message">
                    <img class="avatar" src="public/${pictureLink}" alt="Avatar ${messageUser.user_username}">
                    <a class="username_chat_friend" target="_blank" href="index.php?action=${messageLink}&username=${encodeURIComponent(messageUser.user_username)}"><strong class="strong_text">${messageUser.user_username}</strong></a>
                    <span class="timestamp ${messagePosition}">${new Date(message.chat_date).toLocaleTimeString()}</span>
                </p>
                <p id="last-message">${message.chat_message}</p>
            `;

            messageDiv.innerHTML = messageContent;
            messagesContainer.appendChild(messageDiv);
        });

        console.log('Messages container updated. Now scrolling to bottom.');
        setTimeout(scrollToBottom, 100); // Delay scrolling to ensure container is updated
    }

    // Function to see friend's data
    function showFriendInfo(friend) {
        console.log(friend);

        const pictureLink = friend.user_picture ? `upload/${friend.user_picture}` : "images/defaultprofilepicture.jpg";

        let friendContent = `
        <p id="friendTop">
            <img class="avatar" src="public/${pictureLink}" alt="Avatar ${friend.user_username}">
            <a class="username_chat_friend" target="_blank" href="index.php?action=anotherUser&username=${encodeURIComponent(friend.user_username)}"><strong class="strong_text">${friend.user_username}</strong></a>
        </p>
        <p id="firstToChat">Be the first one to chat <i class="fa-regular fa-comments"></i></p>`;

        if (friendData) {
            friendData.innerHTML = friendContent;
        } else {
            console.error("friendData element not found");
        }

        let messagesContainer = document.getElementById("messages");
        messagesContainer.innerHTML = '';
    };

    // Function to scroll to the bottom of the messages container
    function scrollToBottom() {
        let messagesContainer = document.getElementById("messages");
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Function to set the --vh variable
    function setVhVariable() {
        let vh = window.innerHeight * 0.01; // 1vh
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }

    function checkScreenSize() {
        const isMax900px = window.matchMedia("(max-width: 900px)").matches;
        if (isMax900px) {
            chatInterface.style.display = 'none';
        } else {
            chatInterface.style.display = 'block';
        }
    }

