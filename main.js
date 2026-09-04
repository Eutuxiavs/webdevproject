/**
 * YONZON CLAIM — front-end behavior
 * 1) Fades/lifts elements with class="reveal" into view as they scroll in.
 * 2) Smooth-scrolls in-page nav links instead of the default hard jump.
 */
document.addEventListener('DOMContentLoaded', function () {

  /* ---- Scroll reveal ---- */
  var revealEls = document.querySelectorAll('.reveal');

  if ('IntersectionObserver' in window && revealEls.length) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.15,
      rootMargin: '0px 0px -60px 0px'
    });

    revealEls.forEach(function (el) {
      observer.observe(el);
    });
  } else {
    // No IntersectionObserver support — just show everything.
    revealEls.forEach(function (el) {
      el.classList.add('in-view');
    });
  }

  /* ---- Smooth anchor scrolling ---- */
  document.querySelectorAll('a[href^="#"]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      var targetId = this.getAttribute('href');
      if (targetId.length <= 1) return; // href="#" only

      var target = document.querySelector(targetId);
      if (!target) return;

      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

});

/* ============================================================
   APP LAYER — photo upload preview + live chat
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {

  /* ---- Photo upload preview (claim-add.php, claim-sell.php) ---- */
  var photoInput = document.getElementById('photoInput');
  if (photoInput) {
    photoInput.addEventListener('change', function () {
      var file = this.files && this.files[0];
      var preview = document.getElementById('uploadPreview');
      var placeholder = document.getElementById('uploadPlaceholder');
      if (!file || !preview || !placeholder) return;

      var reader = new FileReader();
      reader.onload = function (e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
        placeholder.style.display = 'none';
      };
      reader.readAsDataURL(file);
    });
  }

  /* ---- Live chat (messages.php) ---- */
  var chatBox = document.getElementById('chatMessages');
  var chatForm = document.getElementById('chatForm');
  if (chatBox && chatForm) {
    var conversationId = chatBox.getAttribute('data-conversation');
    var selfId = chatBox.getAttribute('data-self');
    var lastId = 0;
    var rows = chatBox.querySelectorAll('.chat-bubble-row');
    if (rows.length) {
      lastId = parseInt(rows[rows.length - 1].getAttribute('data-id'), 10) || 0;
    }

    function escapeHtml(str) {
      var div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }

    function appendMessage(msg) {
      var row = document.createElement('div');
      row.className = 'chat-bubble-row' + (String(msg.sender_id) === String(selfId) ? ' mine' : '');
      row.setAttribute('data-id', msg.id);
      var bubble = document.createElement('div');
      bubble.className = 'chat-bubble';
      bubble.innerHTML = escapeHtml(msg.body).replace(/\n/g, '<br>');
      row.appendChild(bubble);
      chatBox.appendChild(row);
      chatBox.scrollTop = chatBox.scrollHeight;
      if (msg.id > lastId) lastId = msg.id;
    }

    chatBox.scrollTop = chatBox.scrollHeight;

    chatForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var input = document.getElementById('chatInput');
      var body = input.value.trim();
      if (!body) return;

      var formData = new FormData(chatForm);
      fetch('api-send-message.php', { method: 'POST', body: formData })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.ok) {
            appendMessage(data.message);
            input.value = '';
          } else {
            alert(data.error || 'Could not send message.');
          }
        })
        .catch(function () { alert('Network error — message not sent.'); });
    });

    // Poll for new messages every 3 seconds.
    setInterval(function () {
      fetch('api-fetch-messages.php?conversation_id=' + encodeURIComponent(conversationId) + '&after_id=' + lastId)
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.ok && data.messages && data.messages.length) {
            data.messages.forEach(appendMessage);
          }
        })
        .catch(function () { /* silent — next poll will retry */ });
    }, 3000);
  }

});
