<?php
require_once __DIR__ . '/../middleware/lgu_auth.php';
requireLguAuth();
$user       = $GLOBALS['lgu_user'];
$role       = $user['role'];
$barangayId = $user['barangay_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BRFE — GIS Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
  <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
  <style>
    #map { height: 100%; width: 100%; }
    .stat-card { transition: transform .15s; }
    .stat-card:hover { transform: translateY(-2px); }
  </style>
</head>
<body class="bg-gray-100 flex flex-col h-screen">

  <header class="bg-blue-800 text-white h-16 flex items-center px-6 gap-4 shrink-0 shadow-md z-10">
    <span class="text-xl">🌊</span>
    <span class="font-bold text-lg tracking-wide">BRFE App</span>
    <span class="text-blue-300 text-sm ml-2">— GIS Dashboard</span>
    <div class="ml-auto flex items-center gap-4 text-sm">
      <?php if ($role === 'LGU_Admin'): ?>
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

    <main class="flex-1 flex flex-col overflow-hidden">

      <!-- Stats bar -->
      <div class="bg-white border-b border-gray-200 px-6 py-3 flex flex-wrap gap-4 items-center shrink-0">
        <div class="stat-card flex items-center gap-3 bg-gray-50 rounded-xl px-4 py-2 border border-gray-200">
          <div class="w-3 h-3 rounded-full bg-blue-500"></div>
          <div>
            <div class="text-xs text-gray-500 font-semibold">Total Evacuees</div>
            <div id="stat-total" class="text-xl font-bold text-gray-900">—</div>
          </div>
        </div>
        <div class="stat-card flex items-center gap-3 bg-green-50 rounded-xl px-4 py-2 border border-green-200">
          <div class="w-3 h-3 rounded-full bg-green-500"></div>
          <div>
            <div class="text-xs text-gray-500 font-semibold">Safe</div>
            <div id="stat-safe" class="text-xl font-bold text-green-700">—</div>
          </div>
        </div>
        <div class="stat-card flex items-center gap-3 bg-yellow-50 rounded-xl px-4 py-2 border border-yellow-200">
          <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
          <div>
            <div class="text-xs text-gray-500 font-semibold">Need Help</div>
            <div id="stat-need" class="text-xl font-bold text-yellow-700">—</div>
          </div>
        </div>
        <div class="stat-card flex items-center gap-3 bg-red-50 rounded-xl px-4 py-2 border border-red-200">
          <div class="w-3 h-3 rounded-full bg-red-500"></div>
          <div>
            <div class="text-xs text-gray-500 font-semibold">In Danger</div>
            <div id="stat-danger" class="text-xl font-bold text-red-700">—</div>
          </div>
        </div>
        <div class="stat-card flex items-center gap-3 bg-orange-50 rounded-xl px-4 py-2 border border-orange-200">
          <div class="w-3 h-3 rounded-full bg-orange-500"></div>
          <div>
            <div class="text-xs text-gray-500 font-semibold">Rescue Requests</div>
            <div id="stat-rescue" class="text-xl font-bold text-orange-700">—</div>
          </div>
        </div>
        <div class="ml-auto flex items-center gap-2 text-xs text-gray-400">
          <div id="live-dot" class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
          <span id="last-update">Live</span>
        </div>
      </div>

      <!-- Map -->
      <div class="flex-1 relative overflow-hidden">
        <div id="map"></div>

        <!-- Legend -->
        <div class="absolute bottom-4 right-4 bg-white/95 rounded-xl shadow-lg p-3 z-[1000] text-xs space-y-1.5">
          <div class="font-bold text-gray-700 mb-2">Legend</div>
          <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-green-500"></div><span>Safe</span></div>
          <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-yellow-500"></div><span>Need Help</span></div>
          <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-red-500"></div><span>In Danger</span></div>
          <div class="flex items-center gap-2"><span class="text-base">🚨</span><span>Rescue</span></div>
          <div class="flex items-center gap-2"><span class="text-base">🏠</span><span>Center (Open)</span></div>
          <div class="flex items-center gap-2"><span class="text-base">🔴</span><span>Center (Full)</span></div>
        </div>

        <!-- Follow mode banner -->
        <div id="follow-banner" class="hidden absolute top-4 left-1/2 -translate-x-1/2 bg-blue-700 text-white px-5 py-2.5 rounded-xl shadow-lg z-[1000] text-xs font-semibold max-w-sm text-center"></div>

        <!-- In-Danger alert banner -->
        <div id="danger-banner" class="hidden absolute top-4 left-1/2 -translate-x-1/2 bg-red-600 text-white px-6 py-3 rounded-xl shadow-lg z-[1000] text-sm font-semibold flex items-center gap-2">
          🚨 <span id="danger-banner-text">An evacuee is IN DANGER!</span>
          <button onclick="document.getElementById('danger-banner').classList.add('hidden')" class="ml-4 text-white/80 hover:text-white">✕</button>
        </div>
      </div>

    </main>
  </div>

  <script src="js/app.js"></script>
  <script>
    // Pass PHP role/barangay to JS
    var DASHBOARD_ROLE        = '<?= htmlspecialchars($role) ?>';
    var DASHBOARD_BARANGAY_ID = <?= $barangayId ? (int)$barangayId : 'null' ?>;
  </script>
  <script src="js/map.js"></script>
</body>
</html>
