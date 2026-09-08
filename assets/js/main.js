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

/* ============================================================
   FLOATING CHAT WIDGET
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  var widget = document.getElementById('chatWidget');
  if (!widget) return;

  var toggle      = document.getElementById('cwToggle');
  var listView     = document.getElementById('cwListView');
  var threadView   = document.getElementById('cwThreadView');
  var backBtn      = document.getElementById('cwBack');
  var messagesBox  = document.getElementById('cwMessages');
  var form         = document.getElementById('cwForm');
  var convIdInput  = document.getElementById('cwConvId');
  var threadName   = document.getElementById('cwThreadName');
  var threadItem   = document.getElementById('cwThreadItem');
  var selfId       = widget.getAttribute('data-self');
  var lastId       = 0;
  var pollTimer    = null;

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function appendBubble(msg) {
    var row = document.createElement('div');
    row.className = 'chat-bubble-row' + (String(msg.sender_id) === String(selfId) ? ' mine' : '');
    var bubble = document.createElement('div');
    bubble.className = 'chat-bubble';
    bubble.innerHTML = escapeHtml(msg.body).replace(/\n/g, '<br>');
    row.appendChild(bubble);
    messagesBox.appendChild(row);
    messagesBox.scrollTop = messagesBox.scrollHeight;
    if (msg.id > lastId) lastId = msg.id;
  }

  function openThread(convId, name, item) {
    convIdInput.value = convId;
    threadName.textContent = name;
    threadItem.textContent = 'Re: ' + item;
    messagesBox.innerHTML = '';
    lastId = 0;
    listView.style.display = 'none';
    threadView.style.display = 'flex';

    fetch('api-fetch-messages.php?conversation_id=' + encodeURIComponent(convId) + '&after_id=0')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.ok) data.messages.forEach(appendBubble);
      });

    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(function () {
      fetch('api-fetch-messages.php?conversation_id=' + encodeURIComponent(convId) + '&after_id=' + lastId)
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.ok && data.messages.length) data.messages.forEach(appendBubble);
        })
        .catch(function () {});
    }, 3000);
  }

  toggle.addEventListener('click', function () {
    widget.classList.toggle('open');
  });

  widget.querySelectorAll('.cw-convo').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openThread(this.getAttribute('data-conversation'), this.getAttribute('data-name'), this.getAttribute('data-item'));
    });
  });

  if (backBtn) {
    backBtn.addEventListener('click', function () {
      threadView.style.display = 'none';
      listView.style.display = 'flex';
      if (pollTimer) clearInterval(pollTimer);
    });
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var input = document.getElementById('cwInput');
      var body = input.value.trim();
      if (!body) return;

      var formData = new FormData(form);
      fetch('api-send-message.php', { method: 'POST', body: formData })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.ok) {
            appendBubble(data.message);
            input.value = '';
          } else {
            alert(data.error || 'Could not send message.');
          }
        });
    });
  }

  // If the page requested a specific conversation open on load
  // (e.g. after clicking "Message Seller"), open it automatically.
  var autoOpen = widget.getAttribute('data-open-conversation');
  if (autoOpen) {
    var convo = widget.querySelector('.cw-convo[data-conversation="' + autoOpen + '"]');
    if (convo) {
      widget.classList.add('open');
      convo.click();
    }
  }
});

/* ============================================================
   CUSTOM CONFIRM MODAL
   Replaces native confirm() dialogs on forms marked data-confirm="...".
   ============================================================ */
function yzConfirm(message, title) {
  return new Promise(function (resolve) {
    var overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML =
      '<div class="modal-card">' +
        (title ? '<div class="modal-title">' + title + '</div>' : '') +
        '<div class="modal-msg"></div>' +
        '<div class="modal-actions">' +
          '<button type="button" class="modal-btn" data-choice="cancel">Cancel</button>' +
          '<button type="button" class="modal-btn danger" data-choice="confirm">Confirm</button>' +
        '</div>' +
      '</div>';
    overlay.querySelector('.modal-msg').textContent = message;
    document.body.appendChild(overlay);
    requestAnimationFrame(function () { overlay.classList.add('open'); });

    function close(result) {
      overlay.classList.remove('open');
      setTimeout(function () { overlay.remove(); }, 180);
      resolve(result);
    }

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) close(false);
      var choice = e.target.getAttribute('data-choice');
      if (choice === 'confirm') close(true);
      if (choice === 'cancel') close(false);
    });
  });
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (form.dataset.confirmed === '1') return; // already confirmed, let it through
      e.preventDefault();
      yzConfirm(form.dataset.confirm, form.dataset.confirmTitle || 'Please confirm').then(function (ok) {
        if (ok) {
          form.dataset.confirmed = '1';
          form.submit();
        }
      });
    });
  });
});

/* ============================================================
   CLIENT-SIDE FORM VALIDATION
   Real JS validation with inline messages, on top of (never instead
   of) the server-side validation every form already has. This is
   purely a UX layer — the server never trusts this and re-validates
   everything itself.
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form.js-validate').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var valid = true;

      form.querySelectorAll('[required]').forEach(function (field) {
        clearFieldError(field);
        if (!field.value.trim()) {
          showFieldError(field, 'This field is required.');
          valid = false;
        } else if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
          showFieldError(field, 'Enter a valid email address.');
          valid = false;
        } else if (field.type === 'password' && field.minLength > 0 && field.value.length < field.minLength) {
          showFieldError(field, 'Must be at least ' + field.minLength + ' characters.');
          valid = false;
        } else if (field.type === 'number') {
          var num = parseFloat(field.value);
          var min = field.min !== '' ? parseFloat(field.min) : null;
          if (isNaN(num) || (min !== null && num < min)) {
            showFieldError(field, 'Enter a valid amount.');
            valid = false;
          }
        }
      });

      // Password-confirmation matching, wherever both fields exist on the same form.
      var pw = form.querySelector('input[name="password"]');
      var confirm = form.querySelector('input[name="confirm"]');
      if (pw && confirm && confirm.value && pw.value !== confirm.value) {
        showFieldError(confirm, 'Passwords do not match.');
        valid = false;
      }

      if (!valid) e.preventDefault();
    });
  });

  function showFieldError(field, message) {
    field.classList.add('field-invalid');
    var msg = document.createElement('div');
    msg.className = 'field-error-msg';
    msg.textContent = message;
    field.insertAdjacentElement('afterend', msg);
  }

  function clearFieldError(field) {
    field.classList.remove('field-invalid');
    var next = field.nextElementSibling;
    if (next && next.classList.contains('field-error-msg')) next.remove();
  }
});