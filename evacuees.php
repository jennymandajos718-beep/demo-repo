<?php
require_once __DIR__ . '/../middleware/lgu_auth.php';
requireLguAuth();
$user       = $GLOBALS['lgu_user'];
$isAdmin    = $user['role'] === 'LGU_Admin';
$barangayId = $user['barangay_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BRFE — Evacuees</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex flex-col h-screen">

  <header class="bg-blue-800 text-white h-16 flex items-center px-6 gap-4 shrink-0 shadow-md z-10">
    <span class="text-xl">🌊</span>
    <span class="font-bold text-lg tracking-wide">BRFE App</span>
    <span class="text-blue-300 text-sm ml-2">— Evacuee Monitoring</span>
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

    <main class="flex-1 overflow-y-auto p-6">
      <div class="max-w-7xl mx-auto">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Evacuee Monitoring</h2>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-end">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
            <select id="filter-status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">All Statuses</option>
              <option value="Safe">🟢 Safe</option>
              <option value="Need_Assistance">🟡 Need Assistance</option>
              <option value="In_Danger">🔴 In Danger</option>
            </select>
          </div>

          <?php if ($isAdmin): ?>
          <!-- LGU Admin: can filter by any barangay -->
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Barangay</label>
            <select id="filter-barangay" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">All Barangays</option>
            </select>
          </div>
          <?php else: ?>
          <!-- Barangay Official: locked to their own barangay -->
          <input type="hidden" id="filter-barangay" value="<?= (int)$barangayId ?>" />
          <?php endif; ?>

          <button onclick="loadEvacuees()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
            Apply Filters
          </button>
          <span id="evacuee-count" class="ml-auto text-sm text-gray-500"></span>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Name</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Barangay</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Contact</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Latitude</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Longitude</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Last GPS</th>
              </tr>
            </thead>
            <tbody id="evacuee-tbody">
              <tr><td colspan="7" class="text-center py-8 text-gray-400">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script src="js/app.js"></script>
  <script>
    var IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;

    var STATUS_BADGE = {
      Safe:            '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">🟢 Safe</span>',
      Need_Assistance: '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">🟡 Need Help</span>',
      In_Danger:       '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">🔴 In Danger</span>',
    };

    function fmtDate(iso) {
      if (!iso) return '—';
      try { return new Date(iso).toLocaleString(); } catch(e) { return iso; }
    }

    function fmtCoord(val) {
      if (val === null || val === undefined || val === '') return '<span class="text-gray-300">—</span>';
      return '<span class="font-mono text-xs text-gray-600">' + parseFloat(val).toFixed(6) + '</span>';
    }

    function renderRow(u) {
      var badge = STATUS_BADGE[u.status] || '<span class="text-gray-400">—</span>';
      var loc   = u.last_location_at ? fmtDate(u.last_location_at) : '<span class="text-gray-300">No GPS</span>';
      return '<tr id="evacuee-row-' + u.id + '" class="border-b border-gray-100 hover:bg-gray-50">' +
        '<td class="px-4 py-3 font-medium text-gray-900">' + esc(u.full_name) + '</td>' +
        '<td class="px-4 py-3 text-gray-600">' + esc(u.barangay_name || '—') + '</td>' +
        '<td class="px-4 py-3">' + badge + '</td>' +
        '<td class="px-4 py-3 text-gray-600">' + esc(u.contact_no || '—') + '</td>' +
        '<td class="px-4 py-3">' + fmtCoord(u.latitude) + '</td>' +
        '<td class="px-4 py-3">' + fmtCoord(u.longitude) + '</td>' +
        '<td class="px-4 py-3 text-gray-500 text-xs">' + loc + '</td>' +
        '</tr>';
    }

    function loadEvacuees() {
      var status   = document.getElementById('filter-status').value;
      var barangay = document.getElementById('filter-barangay').value;
      var params   = new URLSearchParams();
      if (status)   params.set('status', status);
      if (barangay) params.set('barangay_id', barangay);
      var url = '/bagoevac/brfe-web/api/users/list-all?' + params.toString();

      fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
          var list  = data.data || [];
          var tbody = document.getElementById('evacuee-tbody');
          document.getElementById('evacuee-count').textContent = list.length + ' evacuee(s)';
          tbody.innerHTML = list.length
            ? list.map(renderRow).join('')
            : '<tr><td colspan="7" class="text-center py-8 text-gray-400">No evacuees found.</td></tr>';
        })
        .catch(function() {
          document.getElementById('evacuee-tbody').innerHTML =
            '<tr><td colspan="7" class="text-center py-8 text-red-400">Failed to load evacuees.</td></tr>';
        });
    }

    // Load barangay dropdown for LGU Admin only
    if (IS_ADMIN) {
      fetch('/bagoevac/brfe-web/api/barangays/list')
        .then(function(r) { return r.json(); })
        .then(function(data) {
          var list = data.data || [];
          var sel  = document.getElementById('filter-barangay');
          list.forEach(function(b) {
            var opt = document.createElement('option');
            opt.value = b.id;
            opt.textContent = b.name;
            sel.appendChild(opt);
          });
        });
    }

    // Real-time status update
    BrfeApp.on('status_change', function(d) {
      var row = document.getElementById('evacuee-row-' + d.user_id);
      if (row) {
        var badge = STATUS_BADGE[d.status] || '<span class="text-gray-400">—</span>';
        row.querySelectorAll('td')[2].innerHTML = badge;
      }
    });

    // Real-time location update
    BrfeApp.on('location_update', function(d) {
      var row = document.getElementById('evacuee-row-' + d.user_id);
      if (row) {
        var cells = row.querySelectorAll('td');
        if (cells[4]) cells[4].innerHTML = fmtCoord(d.lat || d.latitude);
        if (cells[5]) cells[5].innerHTML = fmtCoord(d.lng || d.longitude);
        if (cells[6]) cells[6].innerHTML = '<span class="text-xs text-gray-500">' + fmtDate(new Date().toISOString()) + '</span>';
      }
    });

    function esc(str) {
      var d = document.createElement('div');
      d.textContent = str || '';
      return d.innerHTML;
    }

    loadEvacuees();
  </script>
</body>
</html>
