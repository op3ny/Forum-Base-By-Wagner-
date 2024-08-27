document.addEventListener('DOMContentLoaded', function() {
    var ws = new WebSocket('ws://localhost:8000');

    ws.onmessage = function(event) {
        var data = JSON.parse(event.data);
        if (data.action === 'new_message') {
            var messageContainer = document.createElement('div');
            messageContainer.className = 'message';
            messageContainer.innerHTML = `<strong>${data.username}</strong>: ${data.message} <small>${new Date(data.created_at).toLocaleTimeString()}</small>`;
            document.querySelector('.messages').appendChild(messageContainer);
            document.querySelector('.messages').scrollTop = document.querySelector('.messages').scrollHeight;
        }
    };

    document.querySelector('#message-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var message = document.querySelector('#message').value;
        var username = prompt("Seu nome:", "anônimo");

        ws.send(JSON.stringify({
            action: 'new_message',
            username: username,
            message: message,
            created_at: new Date().toISOString()
        }));

        document.querySelector('#message').value = '';
    });

    // Carregar mensagens a cada 2 segundos
    setInterval(function() {
        fetch('get_messages.php')
            .then(response => response.json())
            .then(data => {
                var messagesContainer = document.querySelector('.messages');
                messagesContainer.innerHTML = '';
                data.forEach(msg => {
                    var messageHTML = `<div class="message"><strong>${msg.username}</strong>: ${msg.message} <small>${new Date(msg.created_at).toLocaleTimeString()}</small></div>`;
                    messagesContainer.innerHTML += messageHTML;
                });
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            });
    }, 2000);
});
