<?php
require_once __DIR__ . '/../middleware/lgu_auth.php';
requireLguAuth();
$user    = $GLOBALS['lgu_user'];
$isAdmin = $user['role'] === 'LGU_Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BRFE — Evacuation Centers</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="bg-gray-100 flex flex-col h-screen">

  <header class="bg-blue-800 text-white h-16 flex items-center px-6 gap-4 shrink-0 shadow-md z-10">
    <span class="text-xl">🌊</span>
    <span class="font-bold text-lg tracking-wide">BRFE App</span>
    <span class="text-blue-300 text-sm ml-2">— Evacuation Centers</span>
    <div class="ml-auto flex items-center gap-4 text-sm">
      <span class="text-blue-200">👤 <?= htmlspecialchars($user['username']) ?></span>
      <a href="logout.php" class="bg-blue-600 hover:bg-blue-500 px-3 py-1.5 rounded-lg transition-colors">Log Out</a>
    </div>
  </header>

  <div class="flex flex-1 overflow-hidden">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-6">
      <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold text-gray-900">Evacuation Centers</h2>
          <?php if ($isAdmin): ?>
          <button onclick="openModal(null)" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
            + Add Center
          </button>
          <?php endif; ?>
        </div>

        <!-- Error banner -->
        <div id="error-banner" class="hidden mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3"></div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Name</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Address</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Occupancy</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody id="centers-tbody">
              <tr><td colspan="5" class="text-center py-8 text-gray-400">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <!-- Modal -->
  <div id="center-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 overflow-y-auto max-h-[90vh]">
      <h3 id="modal-title" class="text-lg font-bold text-gray-900 mb-4">Add Center</h3>
      <input type="hidden" id="center-id" value="" />

      <div class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Name *</label>
          <input id="c-name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          <p id="err-name" class="text-xs text-red-500 mt-1 hidden"></p>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Address *</label>
          <input id="c-address" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          <p id="err-address" class="text-xs text-red-500 mt-1 hidden"></p>
        </div>
        <div class="grid grid-cols-2 gap-3">          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Latitude *</label>
            <input id="c-lat" type="number" step="any" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. 10.5360" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Longitude *</label>
            <input id="c-lng" type="number" step="any" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. 122.8950" />
          </div>
        </div>
        <p class="text-xs text-gray-400 -mt-1">Or click on the map below to set coordinates.</p>
        <div id="picker-map" style="height:180px;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;"></div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Max Capacity *</label>
            <input id="c-max" type="number" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <p id="err-max_capacity" class="text-xs text-red-500 mt-1 hidden"></p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Occupancy *</label>
            <input id="c-occ" type="number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <p id="err-occupancy" class="text-xs text-red-500 mt-1 hidden"></p>
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Status *</label>
          <select id="c-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="Open">Open</option>
            <option value="Full">Full</option>
            <option value="Closed">Closed</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Barangay</label>
          <input id="c-barangay-display" type="text" readonly
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-500 cursor-not-allowed"
            placeholder="Auto-detected from pin" />
          <input type="hidden" id="c-barangay" value="" />
        </div>
        <p id="err-general" class="text-xs text-red-500 hidden"></p>
      </div>

      <div class="flex gap-3 mt-5">
        <button onclick="closeModal()" class="flex-1 border border-gray-300 rounded-lg py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
        <button onclick="saveCenter()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2 text-sm font-semibold">Save</button>
      </div>
    </div>
  </div>

  <script src="js/app.js"></script>
  <script>
    var STATUS_BADGE = {
      Open:   '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Open</span>',
      Full:   '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">Full</span>',
      Closed: '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Closed</span>',
    };

    // ── Map picker ────────────────────────────────────────────────────────────
    var pickerMap = null;
    var pickerMarker = null;
    var barangayList = [];

    // Load barangays into memory for nearest-match lookup
    fetch('/bagoevac/brfe-web/api/barangays/list')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        barangayList = data.data || [];
      });

    // Reverse geocode via Nominatim and prefill address
    function reverseGeocode(lat, lng) {
      fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&zoom=16&addressdetails=1', {
        headers: { 'Accept-Language': 'en', 'User-Agent': 'BRFE-Web/1.0' }
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data && data.display_name) {
          document.getElementById('c-address').value = data.display_name;
        }
      })
      .catch(function() {});
    }

    // Find nearest barangay from list by haversine
    function nearestBarangay(lat, lng) {
      var best = null, bestDist = Infinity;
      barangayList.forEach(function(b) {
        if (!b.latitude || !b.longitude) return;
        var dLat = (b.latitude  - lat)  * Math.PI / 180;
        var dLng = (b.longitude - lng) * Math.PI / 180;
        var a = Math.sin(dLat/2)*Math.sin(dLat/2) +
                Math.cos(lat*Math.PI/180)*Math.cos(b.latitude*Math.PI/180)*
                Math.sin(dLng/2)*Math.sin(dLng/2);
        var d = 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        if (d < bestDist) { bestDist = d; best = b; }
      });
      return best;
    }

    function initPickerMap(lat, lng) {
      if (!pickerMap) {
        pickerMap = L.map('picker-map', { zoomControl: true }).setView([10.535, 122.840], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(pickerMap);
        pickerMap.on('click', function(e) {
          var clat = e.latlng.lat, clng = e.latlng.lng;
          document.getElementById('c-lat').value = clat.toFixed(7);
          document.getElementById('c-lng').value = clng.toFixed(7);
          if (pickerMarker) pickerMarker.setLatLng(e.latlng);
          else pickerMarker = L.marker(e.latlng).addTo(pickerMap);
          // Prefill address
          reverseGeocode(clat, clng);
          // Auto-select nearest barangay
          var nb = nearestBarangay(clat, clng);
          if (nb) {
            document.getElementById('c-barangay').value = nb.id;
            document.getElementById('c-barangay-display').value = nb.name;
          }
        });
      }
      setTimeout(function() { pickerMap.invalidateSize(); }, 100);
      if (lat && lng) {
        var ll = L.latLng(lat, lng);
        pickerMap.setView(ll, 15);
        if (pickerMarker) pickerMarker.setLatLng(ll);
        else pickerMarker = L.marker(ll).addTo(pickerMap);
      }
    }

    function renderRow(c) {
      var pct  = c.max_capacity > 0 ? Math.round((c.occupancy / c.max_capacity) * 100) : 0;
      var bar  = '<div class="w-full bg-gray-200 rounded-full h-1.5 mt-1"><div class="h-1.5 rounded-full ' + (pct >= 100 ? 'bg-red-500' : pct >= 80 ? 'bg-yellow-500' : 'bg-green-500') + '" style="width:' + Math.min(pct, 100) + '%"></div></div>';
      return '<tr class="border-b border-gray-100 hover:bg-gray-50">' +
        '<td class="px-4 py-3 font-medium text-gray-900">' + esc(c.name) + '</td>' +
        '<td class="px-4 py-3 text-gray-600 text-xs">' + esc(c.address) + '</td>' +
        '<td class="px-4 py-3">' + (STATUS_BADGE[c.op_status] || c.op_status) + '</td>' +
        '<td class="px-4 py-3 text-gray-700 text-xs">' + c.occupancy + ' / ' + c.max_capacity + bar + '</td>' +
        '<td class="px-4 py-3"><button onclick=\'openModal(' + JSON.stringify(c) + ')\' class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-lg">Edit</button></td>' +
        '</tr>';
    }

    function loadCenters() {
      fetch('/bagoevac/brfe-web/api/centers/list_lgu')
        .then(function(r) { return r.json(); })
        .then(function(data) {
          var list  = data.data || [];
          var tbody = document.getElementById('centers-tbody');
          tbody.innerHTML = list.length ? list.map(renderRow).join('') :
            '<tr><td colspan="5" class="text-center py-8 text-gray-400">No centers found.</td></tr>';
        });
    }

    function openModal(c) {
      clearErrors();
      document.getElementById('center-id').value    = c ? c.id : '';
      document.getElementById('c-name').value        = c ? c.name : '';
      document.getElementById('c-address').value     = c ? c.address : '';
      document.getElementById('c-lat').value         = c ? c.latitude : '';
      document.getElementById('c-lng').value         = c ? c.longitude : '';
      document.getElementById('c-max').value         = c ? c.max_capacity : '';
      document.getElementById('c-occ').value         = c ? c.occupancy : '0';
      document.getElementById('c-status').value      = c ? c.op_status : 'Open';
      document.getElementById('c-barangay').value         = c && c.barangay_id ? c.barangay_id : '';
      // Populate display name for existing center
      var brgyDisplay = '';
      if (c && c.barangay_id) {
        var found = barangayList.filter(function(b) { return b.id === c.barangay_id; });
        if (found.length) brgyDisplay = found[0].name;
      }
      document.getElementById('c-barangay-display').value = brgyDisplay;
      document.getElementById('modal-title').textContent = c ? 'Edit Center' : 'Add Center';
      document.getElementById('center-modal').classList.remove('hidden');
      initPickerMap(c ? c.latitude : null, c ? c.longitude : null);
    }

    function closeModal() {
      document.getElementById('center-modal').classList.add('hidden');
    }

    function clearErrors() {
      ['name','address','max_capacity','occupancy','general'].forEach(function(k) {
        var el = document.getElementById('err-' + k);
        if (el) { el.textContent = ''; el.classList.add('hidden'); }
      });
    }

    function showError(field, msg) {
      var el = document.getElementById('err-' + field);
      if (el) { el.textContent = msg; el.classList.remove('hidden'); }
    }

    function saveCenter() {
      clearErrors();
      var id  = document.getElementById('center-id').value;
      var payload = {
        name:         document.getElementById('c-name').value.trim(),
        address:      document.getElementById('c-address').value.trim(),
        latitude:     parseFloat(document.getElementById('c-lat').value),
        longitude:    parseFloat(document.getElementById('c-lng').value),
        max_capacity: parseInt(document.getElementById('c-max').value, 10),
        occupancy:    parseInt(document.getElementById('c-occ').value, 10),
        op_status:    document.getElementById('c-status').value,
        barangay_id:  document.getElementById('c-barangay').value || null,
      };
      if (id) payload.id = parseInt(id, 10);

      fetch('/bagoevac/brfe-web/api/centers/upsert_lgu', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      })
      .then(function(r) { return r.json().then(function(d) { return { data: d }; }); })
      .then(function(res) {
        if (res.data.error) {
          if (res.data.fields) {
            Object.keys(res.data.fields).forEach(function(k) { showError(k, res.data.fields[k]); });
          } else if (res.data.code === 'CAPACITY_EXCEEDED') {
            showError('occupancy', 'Occupancy exceeds max capacity.');
          } else {
            showError('general', res.data.message || 'Save failed.');
          }
          return;
        }
        closeModal();
        loadCenters();
      });
    }

    BrfeApp.on('center_update', function() { loadCenters(); });

    function esc(str) {
      var d = document.createElement('div');
      d.textContent = str || '';
      return d.innerHTML;
    }

    loadCenters();
  </script>
</body>
</html>
