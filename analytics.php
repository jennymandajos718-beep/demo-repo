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
  <title>BRFE — Flood Analytics</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex flex-col h-screen">

  <header class="bg-blue-800 text-white h-16 flex items-center px-6 gap-4 shrink-0 shadow-md z-10">
    <span class="text-xl">🌊</span>
    <span class="font-bold text-lg tracking-wide">BRFE App</span>
    <span class="text-blue-300 text-sm ml-2">— Flood Analytics</span>
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
        <h2 class="text-xl font-bold text-gray-900 mb-4">Flood Analytics</h2>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-end">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Flood Level</label>
            <select id="filter-level" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">All Levels</option>
              <option value="Low">Low</option>
              <option value="Moderate">Moderate</option>
              <option value="High">High</option>
              <option value="Critical">Critical</option>
            </select>
          </div>

          <?php if ($isAdmin): ?>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Barangay</label>
            <select id="filter-barangay" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">All Barangays</option>
            </select>
          </div>
          <?php else: ?>
          <input type="hidden" id="filter-barangay" value="<?= (int)$barangayId ?>" />
          <?php endif; ?>

          <button onclick="loadReports()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
            Apply
          </button>
          <span id="report-count" class="ml-auto text-sm text-gray-500"></span>
        </div>

        <!-- Count cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-green-600" id="cnt-low">—</div>
            <div class="text-xs text-gray-500 mt-1 font-semibold">🟢 Low</div>
          </div>
          <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-yellow-500" id="cnt-moderate">—</div>
            <div class="text-xs text-gray-500 mt-1 font-semibold">🟡 Moderate</div>
          </div>
          <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-orange-500" id="cnt-high">—</div>
            <div class="text-xs text-gray-500 mt-1 font-semibold">🟠 High</div>
          </div>
          <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-red-600" id="cnt-critical">—</div>
            <div class="text-xs text-gray-500 mt-1 font-semibold">🔴 Critical</div>
          </div>
        </div>

        <!-- Reports table -->
        <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Level</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Reporter</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Barangay</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Description</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Coordinates</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Photo</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Submitted</th>
              </tr>
            </thead>
            <tbody id="reports-tbody">
              <tr><td colspan="8" class="text-center py-8 text-gray-400">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <!-- Photo lightbox -->
  <div id="lightbox" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4" onclick="this.classList.add('hidden')">
    <img id="lightbox-img" src="" class="max-w-full max-h-full rounded-xl shadow-2xl" />
  </div>

  <script src="js/app.js"></script>
  <script>
    var IS_ADMIN    = <?= $isAdmin ? 'true' : 'false' ?>;
    var BASE        = '/bagoevac/brfe-web';

    var LEVEL_BADGE = {
      Low:      '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Low</span>',
      Moderate: '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">Moderate</span>',
      High:     '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">High</span>',
      Critical: '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Critical</span>',
    };

    var STATUS_BADGE = {
      Safe:            '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Safe</span>',
      Need_Assistance: '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">Need Help</span>',
      In_Danger:       '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">In Danger</span>',
    };

    function fmtDate(iso) {
      if (!iso) return '—';
      try { return new Date(iso).toLocaleString(); } catch(e) { return iso; }
    }

    function renderRow(r) {
      var coords = (r.latitude && r.longitude)
        ? '<span class="font-mono text-xs">' + r.latitude.toFixed(5) + ', ' + r.longitude.toFixed(5) + '</span>'
        : '—';
      var photo = r.photo_path
        ? '<img src="' + BASE + '/public/' + esc(r.photo_path) + '" class="w-12 h-12 object-cover rounded-lg cursor-pointer border border-gray-200" onclick="showPhoto(\'' + BASE + '/public/' + esc(r.photo_path) + '\')" />'
        : '<span class="text-gray-300 text-xs">—</span>';
      return '<tr class="border-b border-gray-100 hover:bg-gray-50">' +
        '<td class="px-4 py-3">' + (LEVEL_BADGE[r.flood_level] || esc(r.flood_level)) + '</td>' +
        '<td class="px-4 py-3 font-medium text-gray-900">' + esc(r.full_name || '—') + '</td>' +
        '<td class="px-4 py-3 text-gray-600">' + esc(r.barangay_name || '—') + '</td>' +
        '<td class="px-4 py-3">' + (STATUS_BADGE[r.user_status] || '<span class="text-gray-400">—</span>') + '</td>' +
        '<td class="px-4 py-3 text-gray-600 max-w-xs"><div class="truncate" title="' + esc(r.description) + '">' + esc(r.description) + '</div></td>' +
        '<td class="px-4 py-3">' + coords + '</td>' +
        '<td class="px-4 py-3">' + photo + '</td>' +
        '<td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">' + fmtDate(r.submitted_at) + '</td>' +
        '</tr>';
    }

    function loadReports() {
      var level    = document.getElementById('filter-level').value;
      var barangay = document.getElementById('filter-barangay').value;
      var params   = new URLSearchParams();
      if (level)    params.set('flood_level', level);
      if (barangay) params.set('barangay_id', barangay);
      var url = BASE + '/api/reports/list_lgu?' + params.toString();

      fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
          var list   = data.data   || [];
          var counts = data.counts || {};
          document.getElementById('report-count').textContent      = list.length + ' report(s)';
          document.getElementById('cnt-low').textContent           = counts.Low      || 0;
          document.getElementById('cnt-moderate').textContent      = counts.Moderate || 0;
          document.getElementById('cnt-high').textContent          = counts.High     || 0;
          document.getElementById('cnt-critical').textContent      = counts.Critical || 0;
          var tbody = document.getElementById('reports-tbody');
          tbody.innerHTML = list.length ? list.map(renderRow).join('') :
            '<tr><td colspan="8" class="text-center py-8 text-gray-400">No reports found.</td></tr>';
        })
        .catch(function() {
          document.getElementById('reports-tbody').innerHTML =
            '<tr><td colspan="8" class="text-center py-8 text-red-400">Failed to load reports.</td></tr>';
        });
    }

    function showPhoto(url) {
      document.getElementById('lightbox-img').src = url;
      document.getElementById('lightbox').classList.remove('hidden');
    }

    // Load barangay dropdown for LGU Admin only
    if (IS_ADMIN) {
      fetch(BASE + '/api/barangays/list')
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

    // Real-time: reload on new disaster report
    BrfeApp.on('disaster_report', function() { loadReports(); });

    function esc(str) {
      var d = document.createElement('div');
      d.textContent = str || '';
      return d.innerHTML;
    }

    loadReports();
  </script>
</body>
</html>
