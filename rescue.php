<?php
require_once __DIR__ . '/../middleware/lgu_auth.php';
requireLguAuth();
$user = $GLOBALS['lgu_user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BRFE — Rescue Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex flex-col h-screen">

  <header class="bg-blue-800 text-white h-16 flex items-center px-6 gap-4 shrink-0 shadow-md z-10">
    <span class="text-xl">🌊</span>
    <span class="font-bold text-lg tracking-wide">BRFE App</span>
    <span class="text-blue-300 text-sm ml-2">— Rescue Management</span>
    <div class="ml-auto flex items-center gap-4 text-sm">
      <span class="text-blue-200">👤 <?= htmlspecialchars($user['username']) ?></span>
      <a href="logout.php" class="bg-blue-600 hover:bg-blue-500 px-3 py-1.5 rounded-lg transition-colors">Log Out</a>
    </div>
  </header>

  <div class="flex flex-1 overflow-hidden">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-6">
      <div class="max-w-6xl mx-auto">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Rescue Requests</h2>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-end">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
            <select id="filter-status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">All</option>
              <option value="Pending">Pending</option>
              <option value="Ongoing">Ongoing</option>
              <option value="Completed">Completed</option>
            </select>
          </div>
          <button onclick="loadRescues()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
            Apply
          </button>
          <span id="rescue-count" class="ml-auto text-sm text-gray-500"></span>
        </div>

        <!-- Assign modal -->
        <div id="assign-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Assign Responder</h3>
            <input id="assign-responder" type="text" placeholder="Responder name or ID"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <div class="flex gap-3">
              <button onclick="closeAssignModal()" class="flex-1 border border-gray-300 rounded-lg py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
              <button onclick="submitAssign()" class="flex-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2 px-4 text-sm font-semibold">Assign</button>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">#</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Evacuee</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Location</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Rescue Status</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Evacuee Status</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Requested At</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody id="rescue-tbody">
              <tr><td colspan="7" class="text-center py-8 text-gray-400">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script src="js/app.js"></script>
  <script>
    var STATUS_BADGE = {
      Pending:   '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">Pending</span>',
      Ongoing:   '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Ongoing</span>',
      Completed: '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Completed</span>',
    };

    var USER_STATUS_BADGE = {
      Safe:            '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Safe</span>',
      Need_Assistance: '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">Need Help</span>',
      In_Danger:       '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">In Danger</span>',
    };

    var currentAssignId = null;

    function fmtDate(iso) {
      if (!iso) return '—';
      try { return new Date(iso).toLocaleString(); } catch(e) { return iso; }
    }

    function renderActions(r) {
      if (r.req_status === 'Completed') return '<span class="text-gray-400 text-xs">Done</span>';
      var html = '';
      if (r.req_status === 'Pending') {
        html += '<button onclick="openAssignModal(' + r.id + ')" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg mr-2">Assign</button>';
      }
      if (r.req_status === 'Ongoing') {
        html += '<button onclick="markComplete(' + r.id + ')" class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-lg">Complete</button>';
      }
      return html;
    }

    function renderRow(r) {
      var badge      = STATUS_BADGE[r.req_status] || r.req_status;
      var userBadge  = USER_STATUS_BADGE[r.current_status] || '';
      var loc        = r.latitude && r.longitude ? r.latitude.toFixed(4) + ', ' + r.longitude.toFixed(4) : '—';
      var barangay   = r.barangay_name ? esc(r.barangay_name) : '—';
      return '<tr id="rescue-row-' + r.id + '" class="border-b border-gray-100 hover:bg-gray-50">' +
        '<td class="px-4 py-3 text-gray-500">#' + r.id + '</td>' +
        '<td class="px-4 py-3"><div class="font-medium text-gray-900">' + esc(r.full_name || '—') + '</div>' +
          '<div class="text-xs text-gray-400 mt-0.5">' + barangay + '</div></td>' +
        '<td class="px-4 py-3 text-gray-500 text-xs font-mono">' + loc + '</td>' +
        '<td class="px-4 py-3">' + badge + '</td>' +
        '<td class="px-4 py-3">' + userBadge + '</td>' +
        '<td class="px-4 py-3 text-gray-500 text-xs">' + fmtDate(r.requested_at) + '</td>' +
        '<td class="px-4 py-3" id="rescue-actions-' + r.id + '">' + renderActions(r) + '</td>' +
        '</tr>';
    }

    function loadRescues() {
      var status = document.getElementById('filter-status').value;
      var url    = '/bagoevac/brfe-web/api/rescue/list_lgu?' + new URLSearchParams({ status: status }).toString();
      fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
          var list  = data.data || [];
          var tbody = document.getElementById('rescue-tbody');
          document.getElementById('rescue-count').textContent = list.length + ' request(s)';
          tbody.innerHTML = list.length ? list.map(renderRow).join('') :
            '<tr><td colspan="7" class="text-center py-8 text-gray-400">No rescue requests found.</td></tr>';
        });
    }

    function openAssignModal(id) {
      currentAssignId = id;
      document.getElementById('assign-responder').value = '';
      document.getElementById('assign-modal').classList.remove('hidden');
    }

    function closeAssignModal() {
      document.getElementById('assign-modal').classList.add('hidden');
      currentAssignId = null;
    }

    function submitAssign() {
      if (!currentAssignId) return;
      fetch('/bagoevac/brfe-web/api/rescue/update_lgu', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: currentAssignId, req_status: 'Ongoing' }),
      })
      .then(function(r) { return r.json(); })
      .then(function() { closeAssignModal(); loadRescues(); });
    }

    function markComplete(id) {
      if (!confirm('Mark rescue #' + id + ' as Completed?')) return;
      fetch('/bagoevac/brfe-web/api/rescue/update_lgu', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, req_status: 'Completed' }),
      })
      .then(function(r) { return r.json(); })
      .then(function() { loadRescues(); });
    }

    // Real-time updates
    BrfeApp.on('rescue_request', function() { loadRescues(); });
    BrfeApp.on('rescue_status',  function() { loadRescues(); });

    function esc(str) {
      var d = document.createElement('div');
      d.textContent = str || '';
      return d.innerHTML;
    }

    loadRescues();
  </script>
</body>
</html>
