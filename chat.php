<?php
require_once __DIR__ . '/../middleware/lgu_auth.php';
requireLguAuth();
$user    = $GLOBALS['lgu_user'];
$isAdmin = $user['role'] === 'LGU_Admin';
$isBrgy  = $user['role'] === 'Barangay_Official';
$roleColor = $isAdmin ? 'red' : 'green';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BRFE — Chat</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>#msg-list{scroll-behavior:smooth;}</style>
</head>
<body class="bg-gray-100 flex flex-col h-screen">

  <header class="bg-blue-800 text-white h-16 flex items-center px-6 gap-4 shrink-0 shadow-md z-10">
    <span class="text-xl">🌊</span>
    <span class="font-bold text-lg tracking-wide">BRFE App</span>
    <span class="text-blue-300 text-sm ml-2">— Chat</span>
    <div class="ml-auto flex items-center gap-4 text-sm">
      <?php if ($isAdmin): ?>
        <span class="bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">LGU Admin</span>
      <?php else: ?>
        <span class="bg-green-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">Barangay Official</span>
      <?php endif; ?>
      <span class="text-blue-200">👤 <?= htmlspecialchars($user['username']) ?></span>
      <a href="logout.php" class="bg-blue-600 hover:bg-blue-500 px-3 py-1.5 rounded-lg transition-colors">Log Out</a>
    </div>
  </header>

  <div class="flex flex-1 overflow-hidden">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="flex flex-1 overflow-hidden">

      <!-- Left panel: tabs + thread list -->
      <div class="w-72 bg-white border-r border-gray-200 flex flex-col overflow-hidden">

        <!-- Tabs -->
        <div class="flex border-b border-gray-200 text-xs font-semibold">
          <button onclick="switchTab('chat')" id="tab-chat"
            class="flex-1 py-3 text-center tab-btn tab-active">💬 Chat</button>
          <?php if ($isAdmin || $isBrgy): ?>
          <button onclick="switchTab('broadcast')" id="tab-broadcast"
            class="flex-1 py-3 text-center tab-btn">📢 Broadcast</button>
          <?php endif; ?>
          <?php if ($isAdmin): ?>
          <button onclick="switchTab('announcement')" id="tab-announcement"
            class="flex-1 py-3 text-center tab-btn">📣 Announce</button>
          <?php endif; ?>
        </div>

        <!-- Chat thread list -->
        <div id="panel-chat" class="flex-1 overflow-y-auto">
          <div class="text-center py-8 text-gray-400 text-sm">Loading…</div>
        </div>

        <!-- Broadcast/Announcement feed -->
        <div id="panel-broadcast" class="hidden flex-1 overflow-y-auto p-3 space-y-2"></div>
        <div id="panel-announcement" class="hidden flex-1 overflow-y-auto p-3 space-y-2"></div>

        <!-- Action buttons -->
        <div class="p-3 border-t border-gray-200 space-y-2">
          <?php if ($isAdmin || $isBrgy): ?>
          <button onclick="openCompose('broadcast')"
            class="w-full bg-<?= $roleColor ?>-600 hover:bg-<?= $roleColor ?>-700 text-white text-sm font-semibold py-2 rounded-lg transition-colors">
            📢 Send Broadcast
          </button>
          <?php endif; ?>
          <?php if ($isAdmin): ?>
          <button onclick="openCompose('announcement')"
            class="w-full bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold py-2 rounded-lg transition-colors">
            📣 Send Announcement
          </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Right: message pane -->
      <div class="flex-1 flex flex-col overflow-hidden">
        <div id="chat-header" class="px-5 py-3 border-b border-gray-200 bg-white text-sm font-semibold text-gray-700 hidden">
          <span id="chat-header-name"></span>
        </div>
        <div id="msg-list" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
          <div id="msg-placeholder" class="text-center text-gray-400 text-sm pt-16">Select a conversation or send a broadcast.</div>
        </div>
        <div id="input-bar" class="hidden p-3 bg-white border-t border-gray-200 flex gap-2">
          <input id="msg-input" type="text" placeholder="Type a message…" maxlength="500"
            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          <button onclick="sendMessage()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">Send</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Compose modal (broadcast / announcement) -->
  <div id="compose-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
      <h3 id="compose-title" class="text-lg font-bold text-gray-900 mb-1"></h3>
      <p id="compose-sub" class="text-sm text-gray-500 mb-4"></p>
      <textarea id="compose-body" rows="4" placeholder="Type your message…"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 mb-3"></textarea>
      <div class="mb-4">
        <label class="block text-xs font-semibold text-gray-600 mb-1">Expires at (optional)</label>
        <input id="compose-expires" type="datetime-local"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        <p class="text-xs text-gray-400 mt-1">Leave blank to never expire.</p>
      </div>
      <div class="flex gap-3">
        <button onclick="closeCompose()" class="flex-1 border border-gray-300 rounded-lg py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
        <button id="compose-send-btn" onclick="sendCompose()" class="flex-1 text-white rounded-lg py-2 text-sm font-semibold">Send</button>
      </div>
    </div>
  </div>

  <script src="js/app.js"></script>
  <script>
    var activeUserId   = null;
    var activeUserName = '';
    var currentTab     = 'chat';
    var composeType    = 'broadcast';

    var ROLE_COLOR = '<?= $roleColor ?>';

    function fmtTime(iso) {
      if (!iso) return '';
      try { return new Date(iso).toLocaleString(); } catch(e) { return iso; }
    }

    function fmtExpiry(iso) {
      if (!iso) return 'No expiry';
      try { return 'Expires: ' + new Date(iso).toLocaleString(); } catch(e) { return iso; }
    }

    // ── Tabs ───────────────────────────────────────────────────────────────────
    function switchTab(tab) {
      currentTab = tab;
      ['chat','broadcast','announcement'].forEach(function(t) {
        var btn = document.getElementById('tab-' + t);
        var panel = document.getElementById('panel-' + t);
        if (!btn || !panel) return;
        if (t === tab) {
          btn.classList.add('tab-active', 'border-b-2', 'border-blue-600', 'text-blue-700');
          panel.classList.remove('hidden');
        } else {
          btn.classList.remove('tab-active', 'border-b-2', 'border-blue-600', 'text-blue-700');
          panel.classList.add('hidden');
        }
      });
      if (tab === 'broadcast') loadFeed('broadcast');
      if (tab === 'announcement') loadFeed('announcement');
    }

    // ── Thread list ────────────────────────────────────────────────────────────
    function loadThreads() {
      fetch('/bagoevac/brfe-web/api/chat/threads_lgu')
        .then(function(r) { return r.json(); })
        .then(function(data) {
          var list = data.data || [];
          var el   = document.getElementById('panel-chat');
          if (!list.length) {
            el.innerHTML = '<div class="text-center py-8 text-gray-400 text-sm">No conversations yet.</div>';
            return;
          }
          el.innerHTML = list.map(function(u) {
            var active = u.id === activeUserId ? 'bg-blue-50 border-l-4 border-blue-600' : 'hover:bg-gray-50';
            return '<div class="px-4 py-3 cursor-pointer border-b border-gray-100 ' + active + '" onclick="openThread(' + u.id + ', \'' + esc(u.full_name) + '\')">' +
              '<div class="font-semibold text-gray-900 text-sm">' + esc(u.full_name) + '</div>' +
              '<div class="text-xs text-gray-500 truncate mt-0.5">' + esc(u.last_message || 'No messages yet') + '</div>' +
              '</div>';
          }).join('');
        });
    }

    // ── Feed (broadcast / announcement) ───────────────────────────────────────
    function loadFeed(type) {
      fetch('/bagoevac/brfe-web/api/chat/thread_lgu?feed=1')
        .then(function(r) { return r.json(); })
        .then(function(data) {
          var list = (data.data || []).filter(function(m) { return m.msg_type === type; });
          var el   = document.getElementById('panel-' + type);
          if (!list.length) {
            el.innerHTML = '<div class="text-center py-8 text-gray-400 text-sm">No ' + type + 's yet.</div>';
            return;
          }
          el.innerHTML = list.map(function(m) {
            var isAdmin = m.sender_role === 'LGU_Admin';
            var color   = isAdmin ? 'red' : 'green';
            var label   = isAdmin ? 'LGU Admin' : 'Barangay Official';
            var brgy    = m.sender_barangay ? ' · ' + esc(m.sender_barangay) : '';
            return '<div class="bg-white rounded-xl border border-gray-200 p-3 shadow-sm">' +
              '<div class="flex items-center gap-2 mb-1">' +
              '<span class="text-xs font-bold text-' + color + '-600 bg-' + color + '-50 px-2 py-0.5 rounded-full">' + label + '</span>' +
              '<span class="text-xs text-gray-500">' + esc(m.sender_name) + brgy + '</span>' +
              '</div>' +
              '<p class="text-sm text-gray-800 mb-1">' + esc(m.body) + '</p>' +
              '<div class="flex justify-between text-xs text-gray-400">' +
              '<span>' + fmtTime(m.sent_at) + '</span>' +
              '<span class="' + (m.expires_at ? 'text-orange-500' : '') + '">' + fmtExpiry(m.expires_at) + '</span>' +
              '</div></div>';
          }).join('');
        });
    }

    // ── Thread ─────────────────────────────────────────────────────────────────
    function openThread(userId, userName) {
      activeUserId   = userId;
      activeUserName = userName;
      document.getElementById('chat-header').classList.remove('hidden');
      document.getElementById('chat-header-name').textContent = '💬 ' + userName;
      document.getElementById('input-bar').classList.remove('hidden');
      document.getElementById('msg-placeholder').classList.add('hidden');
      loadMessages();
      loadThreads();
    }

    function loadMessages() {
      if (!activeUserId) return;
      fetch('/bagoevac/brfe-web/api/chat/thread_lgu?with=' + activeUserId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
          var list = data.data || [];
          var el   = document.getElementById('msg-list');
          el.innerHTML = list.length ? list.map(renderMsg).join('') :
            '<div class="text-center text-gray-400 text-sm pt-8">No messages yet.</div>';
          el.scrollTop = el.scrollHeight;
        });
    }

    function renderMsg(m) {
      var isLgu   = m.sender_type === 'lgu';
      var align   = isLgu ? 'justify-end' : 'justify-start';
      var isAdmin = m.sender_role === 'LGU_Admin';
      var bubbleCls = isLgu
        ? (isAdmin ? 'bg-red-600 text-white' : 'bg-green-600 text-white')
        : 'bg-white text-gray-900 border border-gray-200';
      var nameLabel = isLgu
        ? esc(m.sender_name) + (m.sender_barangay ? ' · ' + esc(m.sender_barangay) : '') +
          ' <span class="opacity-70">(' + (isAdmin ? 'LGU Admin' : 'Barangay Official') + ')</span>'
        : esc(m.sender_name);
      return '<div class="flex ' + align + '">' +
        '<div class="max-w-xs">' +
        '<div class="text-xs text-gray-400 mb-1 ' + (isLgu ? 'text-right' : '') + '">' + nameLabel + '</div>' +
        '<div class="rounded-2xl px-4 py-2 ' + bubbleCls + '">' +
        '<div class="text-sm">' + esc(m.body) + '</div>' +
        '<div class="text-xs mt-1 opacity-60 text-right">' + fmtTime(m.sent_at) + '</div>' +
        '</div></div></div>';
    }

    function sendMessage() {
      var text = document.getElementById('msg-input').value.trim();
      if (!text || !activeUserId) return;
      document.getElementById('msg-input').value = '';
      fetch('/bagoevac/brfe-web/api/chat/thread_lgu', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ recipient_id: activeUserId, message: text }),
      }).then(function() { loadMessages(); loadThreads(); });
    }

    document.getElementById('msg-input').addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    // ── Compose modal ──────────────────────────────────────────────────────────
    function openCompose(type) {
      composeType = type;
      document.getElementById('compose-body').value = '';
      document.getElementById('compose-expires').value = '';
      var isAnnounce = type === 'announcement';
      document.getElementById('compose-title').textContent = isAnnounce ? '📣 Send Announcement' : '📢 Send Broadcast';
      document.getElementById('compose-sub').textContent   = isAnnounce
        ? 'Announcement sent to ALL evacuees city-wide.'
        : 'Broadcast sent to evacuees in your scope.';
      var btn = document.getElementById('compose-send-btn');
      btn.className = 'flex-1 text-white rounded-lg py-2 text-sm font-semibold ' +
        (isAnnounce ? 'bg-orange-500 hover:bg-orange-600' : 'bg-' + ROLE_COLOR + '-600 hover:bg-' + ROLE_COLOR + '-700');
      document.getElementById('compose-modal').classList.remove('hidden');
    }

    function closeCompose() {
      document.getElementById('compose-modal').classList.add('hidden');
    }

    function sendCompose() {
      var msg     = document.getElementById('compose-body').value.trim();
      var expires = document.getElementById('compose-expires').value;
      if (!msg) return;
      fetch('/bagoevac/brfe-web/api/chat/broadcast', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ body: msg, msg_type: composeType, expires_at: expires || null }),
      })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        closeCompose();
        if (d.error) { alert('Error: ' + (d.message || 'Send failed.')); return; }
        alert((composeType === 'announcement' ? 'Announcement' : 'Broadcast') + ' sent to ' + (d.sent_to || 0) + ' evacuees.');
        loadFeed(composeType);
      });
    }

    // ── Real-time ──────────────────────────────────────────────────────────────
    BrfeApp.on('chat_message', function(d) {
      loadThreads();
      if (d.msg_type === 'broadcast' || d.msg_type === 'announcement') {
        loadFeed(d.msg_type);
      } else if (d.sender_id === activeUserId || d.recipient_id === activeUserId) {
        loadMessages();
      }
    });

    function esc(str) {
      var d = document.createElement('div');
      d.textContent = str || '';
      return d.innerHTML;
    }

    // Init
    switchTab('chat');
    loadThreads();
  </script>

  <style>
    .tab-active { border-bottom: 2px solid #2563eb; color: #1d4ed8; }
  </style>
</body>
</html>
