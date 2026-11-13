<x-portfolio-layout>

@php
    $summary = $journeyData['summary'] ?? [];
    $trips = $journeyData['trips'] ?? [];
    $places = $journeyData['places'] ?? [];
    $segments = $journeyData['semantic_segments'] ?? [];

    $thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $formatThaiDate = function ($dateStr) use ($thaiMonths) {
        if (!$dateStr) return '-';
        $d = \Illuminate\Support\Carbon::parse($dateStr);
        return $d->day . ' ' . $thaiMonths[$d->month] . ' ' . ($d->year + 543);
    };

    $gpConnected = $journeyData['google_photos']['connected'] ?? false;
    $gpPhotos = $journeyData['google_photos']['photos'] ?? [];
@endphp

{{-- Leaflet CSS & JS via Cloudflare CDN (No SRI hash to ensure guaranteed loading) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<style>
    /* Explicit Map Dimensions (Guaranteed rendering regardless of Tailwind compilation) */
    #journey-map {
        width: 100% !important;
        height: 560px !important;
        min-height: 560px !important;
        position: relative !important;
        z-index: 1 !important;
        background: #0d1117 !important;
    }
    .leaflet-container {
        background: #0d1117 !important;
        font-family: inherit !important;
        width: 100% !important;
        height: 100% !important;
    }
    /* Midnight Dark Filter for OpenStreetMap tiles (No API key needed, no watermark) */
    .leaflet-tile-pane {
        filter: brightness(0.68) invert(0.94) contrast(1.35) hue-rotate(200deg) saturate(0.35);
    }
    .leaflet-popup-content-wrapper {
        background: #161922 !important;
        color: #f1f5f9 !important;
        border: 1px solid #334155 !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
    }
    .leaflet-popup-tip {
        background: #161922 !important;
        border: 1px solid #334155 !important;
    }
    .pulse-marker {
        border-radius: 50%;
        animation: pulse-ring 2s infinite;
    }
    @keyframes pulse-ring {
        0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
        70% { box-shadow: 0 0 0 12px rgba(245, 158, 11, 0); }
        100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }
    /* Sleek Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 5px;
        height: 5px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #0d1117;
        border-radius: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #334155;
        border-radius: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #475569;
    }
    .thailand-region-tooltip {
        background: rgba(15, 23, 42, 0.95) !important;
        color: #f8fafc !important;
        border: 1px solid #334155 !important;
        border-radius: 10px !important;
        padding: 6px 10px !important;
        box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.6) !important;
        font-size: 11px !important;
    }
    .thailand-region-tooltip::before {
        display: none !important;
    }
</style>

{{-- Preload data & Register Alpine Component before DOM parse --}}
<script>
window.__JOURNEY_RAW_DATA__ = {!! json_encode($journeyData, JSON_UNESCAPED_UNICODE) !!};
window.__REGION_TOGGLE_URL__ = "{{ route('portfolio.journey.regions.toggle') }}";
window.__CSRF_TOKEN__ = "{{ csrf_token() }}";

(function() {
    let leafletMap = null;
    let polylines = [];
    let placeMarkers = [];
    let animMarker = null;
    let animTimer = null;
    let regionGeoLayer = null;
    let regionGeoPopup = null;

    const COLOR_PALETTES = {
        emerald: { name: 'มรกต', autoFill: '#10b981', autoBorder: '#34d399', manualFill: '#14b8a6', manualBorder: '#5eead4', swatch: 'bg-emerald-500' },
        amber:   { name: 'อำพัน', autoFill: '#f59e0b', autoBorder: '#fbbf24', manualFill: '#f97316', manualBorder: '#fdba74', swatch: 'bg-amber-500' },
        sky:     { name: 'ฟ้าไซเบอร์', autoFill: '#0ea5e9', autoBorder: '#38bdf8', manualFill: '#6366f1', manualBorder: '#a5b4fc', swatch: 'bg-sky-500' },
        rose:    { name: 'กุหลาบ', autoFill: '#f43f5e', autoBorder: '#fb7185', manualFill: '#ec4899', manualBorder: '#f472b6', swatch: 'bg-rose-500' },
        violet:  { name: 'ไวโอเลต', autoFill: '#8b5cf6', autoBorder: '#a78bfa', manualFill: '#d946ef', manualBorder: '#e879f9', swatch: 'bg-violet-500' }
    };

    const REGION_LABELS = {
        North: 'ภาคเหนือ',
        NorthEast: 'ภาคอีสาน',
        Central: 'ภาคกลาง',
        East: 'ภาคตะวันออก',
        West: 'ภาคตะวันตก',
        South: 'ภาคใต้'
    };

    const registerComponent = () => {
        if (!window.Alpine) return;

        const rawRegions = window.__JOURNEY_RAW_DATA__.thailand_regions || {};

        Alpine.data('journeyMap', () => ({
            trips: window.__JOURNEY_RAW_DATA__.trips || [],
            places: window.__JOURNEY_RAW_DATA__.places || [],
            lifetimeTrips: window.__JOURNEY_RAW_DATA__.lifetime_trips || [],
            showLifetimeOverview: false,
            lifetimeLayerGroup: null,
            selectedTripId: 'all',
            dateFilter: 'all',
            showUploadModal: false,
            isAnimating: false,
            animStatus: '',
            animSpeed: 0,

            // Thailand Conquest (Provinces & Amphoes) state
            regionMapMode: 'province', // 'off' | 'province' | 'amphoe'
            keepTripsInRegionMode: true,
            paintMode: false,
            colorScheme: rawRegions.color_scheme || 'emerald',
            autoProvinces: rawRegions.auto_provinces || {},
            autoAmphoes: rawRegions.auto_amphoes || {},
            manualProvinces: (rawRegions.manual_provinces || []).map(String),
            manualAmphoes: (rawRegions.manual_amphoes || []).map(String),
            hiddenProvinces: (rawRegions.hidden_provinces || []).map(String),
            hiddenAmphoes: (rawRegions.hidden_amphoes || []).map(String),
            provinceCatalog: rawRegions.province_catalog || [],
            totalProvincesCount: rawRegions.total_provinces || 77,
            totalAmphoesCount: rawRegions.total_amphoes || 928,
            provincesGeo: null,
            amphoesGeo: null,
            amphoesLoading: false,
            provincesLoading: false,
            selectedProvinceFilter: 'all',

            // Explorer Checklist state
            selectedRegionTab: 'provinces', // 'provinces' | 'amphoes'
            regionFilter: 'all',
            statusFilter: 'all', // 'all' | 'visited' | 'unvisited'
            regionSearch: '',
            amphoeCatalog: [],
            amphoeProvinceFilter: 'all',
            amphoeStatusFilter: 'all',
            amphoeSearch: '',
            savingRegionCode: null,

            get regionPalettes() {
                return COLOR_PALETTES;
            },

            get regionLabels() {
                return REGION_LABELS;
            },

            getProvinceStatus(code) {
                const c = String(code);
                const isAuto = Boolean(this.autoProvinces[c]) && !this.hiddenProvinces.includes(c);
                const isManual = this.manualProvinces.includes(c);
                return {
                    visited: isAuto || isManual,
                    isAuto,
                    isManual,
                    rawAuto: Boolean(this.autoProvinces[c]),
                    meta: this.autoProvinces[c] || null
                };
            },

            getAmphoeStatus(code) {
                const c = String(code);
                const isAuto = Boolean(this.autoAmphoes[c]) && !this.hiddenAmphoes.includes(c);
                const isManual = this.manualAmphoes.includes(c);
                return {
                    visited: isAuto || isManual,
                    isAuto,
                    isManual,
                    rawAuto: Boolean(this.autoAmphoes[c]),
                    meta: this.autoAmphoes[c] || null
                };
            },

            get visitedProvincesSummary() {
                let total = 0, autoCnt = 0, manualCnt = 0;
                for (const p of this.provinceCatalog) {
                    const st = this.getProvinceStatus(p.code);
                    if (st.visited) {
                        total++;
                        if (st.isAuto) autoCnt++;
                        else if (st.isManual) manualCnt++;
                    }
                }
                const pct = this.totalProvincesCount > 0 ? Math.round((total / this.totalProvincesCount) * 100) : 0;
                return { total, autoCnt, manualCnt, pct };
            },

            get visitedAmphoesSummary() {
                const seen = new Set();
                for (const [c] of Object.entries(this.autoAmphoes)) {
                    if (!this.hiddenAmphoes.includes(String(c))) seen.add(String(c));
                }
                for (const c of this.manualAmphoes) {
                    seen.add(String(c));
                }
                const total = seen.size;
                const pct = this.totalAmphoesCount > 0 ? Math.round((total / this.totalAmphoesCount) * 100) : 0;
                return { total, pct };
            },

            get regionalBreakdown() {
                const order = ['Central', 'North', 'NorthEast', 'East', 'West', 'South'];
                const buckets = {};
                for (const r of order) {
                    buckets[r] = { key: r, label: REGION_LABELS[r] || r, visited: 0, total: 0 };
                }
                for (const p of this.provinceCatalog) {
                    const rk = p.reg || 'Central';
                    if (!buckets[rk]) buckets[rk] = { key: rk, label: REGION_LABELS[rk] || rk, visited: 0, total: 0 };
                    buckets[rk].total++;
                    if (this.getProvinceStatus(p.code).visited) buckets[rk].visited++;
                }
                return order.map(k => buckets[k]).filter(b => b && b.total > 0);
            },

            get filteredProvincesList() {
                const q = this.regionSearch.trim().toLowerCase();
                return this.provinceCatalog.filter(p => {
                    if (this.regionFilter !== 'all' && p.reg !== this.regionFilter) return false;
                    const st = this.getProvinceStatus(p.code);
                    if (this.statusFilter === 'visited' && !st.visited) return false;
                    if (this.statusFilter === 'unvisited' && st.visited) return false;
                    if (q && !p.th.toLowerCase().includes(q) && !p.en.toLowerCase().includes(q)) return false;
                    return true;
                });
            },

            get filteredAmphoesList() {
                const q = this.amphoeSearch.trim().toLowerCase();
                return this.amphoeCatalog.filter(a => {
                    if (this.amphoeProvinceFilter !== 'all' && a.pro_code !== this.amphoeProvinceFilter) return false;
                    const st = this.getAmphoeStatus(a.code);
                    if (this.amphoeStatusFilter === 'visited' && !st.visited) return false;
                    if (this.amphoeStatusFilter === 'unvisited' && st.visited) return false;
                    if (q && !a.th.toLowerCase().includes(q) && !a.en.toLowerCase().includes(q) && !a.pro_th.toLowerCase().includes(q)) return false;
                    return true;
                }).slice(0, 120);
            },

            visitedAmphoeCountForProvince(proCode) {
                const pc = String(proCode);
                if (this.amphoeCatalog.length > 0) {
                    let count = 0;
                    for (const a of this.amphoeCatalog) {
                        if (a.pro_code === pc && this.getAmphoeStatus(a.code).visited) count++;
                    }
                    return count;
                }
                // Estimate from autoAmphoes prefix (first 2 digits match pro_code)
                let count = 0;
                for (const ac of Object.keys(this.autoAmphoes)) {
                    if (ac.startsWith(pc) && !this.hiddenAmphoes.includes(ac)) count++;
                }
                for (const ac of this.manualAmphoes) {
                    if (ac.startsWith(pc) && (!this.autoAmphoes[ac] || this.hiddenAmphoes.includes(ac))) count++;
                }
                return count;
            },

            get filteredTrips() {
                if (this.dateFilter === 'all') return this.trips;
                if (this.dateFilter === 'today') return this.trips.filter(t => t.date === '2026-11-10');
                if (this.dateFilter === '2026-11-09') return this.trips.filter(t => t.date === '2026-11-09');
                if (this.dateFilter === '2026-11-08') return this.trips.filter(t => t.date === '2026-11-08');
                if (this.dateFilter === '2026-11') return this.trips.filter(t => t.date.startsWith('2026-11'));
                if (this.dateFilter === '2026-10') return this.trips.filter(t => t.date.startsWith('2026-10'));
                if (this.dateFilter === 'earlier') return this.trips.filter(t => t.date < '2026-10-01');
                return this.trips.filter(t => t.date === this.dateFilter);
            },

            init() {
                const bootMap = () => {
                    if (window.L && document.getElementById('journey-map')) {
                        this.initMap();
                    } else {
                        setTimeout(bootMap, 50);
                    }
                };
                this.$nextTick(bootMap);
            },

            initMap() {
                if (leafletMap) return;

                leafletMap = L.map('journey-map', {
                    center: [13.8238, 100.5552],
                    zoom: 12,
                    zoomControl: true
                });

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(leafletMap);

                this.renderAllPaths();
                this.renderPlaces();
                this.renderRegionLayer(false);

                // Expose popup bridge callbacks cleanly
                window.__journeyComponent = this;

                // Leaflet container sizing fix
                setTimeout(() => {
                    if (leafletMap) leafletMap.invalidateSize();
                }, 100);
                setTimeout(() => {
                    if (leafletMap) {
                        leafletMap.invalidateSize();
                        this.renderAllPaths();
                    }
                }, 400);
            },

            clearLayers() {
                polylines.forEach(p => leafletMap.removeLayer(p));
                polylines = [];
            },

            renderAllPaths() {
                this.clearLayers();
                if (this.regionMapMode !== 'off' && !this.keepTripsInRegionMode) {
                    return;
                }
                const palette = ['#38bdf8', '#f59e0b', '#a855f7', '#10b981', '#ec4899', '#06b6d4', '#eab308', '#8b5cf6', '#14b8a6', '#f43f5e', '#6366f1', '#fb923c'];

                const allLatLngs = [];
                const tripsToRender = (this.dateFilter === 'all') ? this.trips.slice(0, 45) : this.filteredTrips;

                tripsToRender.forEach(t => {
                    const latlngs = t.path.map(p => [p[0], p[1]]);
                    if (latlngs.length > 0) {
                        const color = palette[(t.id - 1) % palette.length];
                        const poly = L.polyline(latlngs, {
                            color: color,
                            weight: 4,
                            opacity: 0.85,
                            smoothFactor: 1
                        }).addTo(leafletMap);

                        poly.bindPopup(`
                            <div class="p-1 space-y-1">
                                <div class="font-bold text-xs text-amber-400">${t.title}</div>
                                <div class="text-[11px] text-slate-300">ระยะทาง: <strong>${t.distance_km} กม.</strong></div>
                                <div class="text-[11px] text-slate-300">ความเร็วสูงสุด: <strong>${t.max_speed_kmh} กม./ชม.</strong></div>
                                <div class="text-[11px] text-slate-400">จุดบันทึก: ${t.points_count} จุด</div>
                            </div>
                        `);

                        polylines.push(poly);
                        allLatLngs.push(...latlngs);
                    }
                });

                if (allLatLngs.length > 0) {
                    leafletMap.fitBounds(L.latLngBounds(allLatLngs), { padding: [40, 40] });
                }
            },

            renderPlaces() {
                placeMarkers.forEach(m => leafletMap.removeLayer(m));
                placeMarkers = [];

                // Home Marker
                const homeIcon = L.divIcon({
                    className: 'custom-pin',
                    html: '<div style="background:#10b981; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid white; box-shadow:0 0 10px rgba(16,185,129,0.8); font-size:14px;">🏠</div>',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });
                const homeM = L.marker([13.823862, 100.555256], { icon: homeIcon })
                    .addTo(leafletMap)
                    .bindPopup('<div class="p-1 font-bold text-xs text-emerald-400">🏠 บ้าน (HOME)</div><div class="text-[11px] text-slate-300">พิกัด 13.8238, 100.5552</div>');
                placeMarkers.push(homeM);

                // Work Marker
                const workIcon = L.divIcon({
                    className: 'custom-pin',
                    html: '<div style="background:#f97316; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid white; box-shadow:0 0 10px rgba(249,115,22,0.8); font-size:14px;">💼</div>',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });
                const workM = L.marker([13.819086, 100.557508], { icon: workIcon })
                    .addTo(leafletMap)
                    .bindPopup('<div class="p-1 font-bold text-xs text-orange-400">💼 ที่ทำงาน (WORK)</div><div class="text-[11px] text-slate-300">พิกัด 13.8190, 100.5575</div>');
                placeMarkers.push(workM);
            },

            getActiveTrip() {
                if (this.selectedTripId === 'all') return null;
                const id = parseInt(this.selectedTripId);
                return this.trips.find(t => t.id === id) || null;
            },

            prevTrip() {
                if (this.selectedTripId === 'all') {
                    if (this.trips.length > 0) this.selectTrip(1);
                    return;
                }
                const curId = parseInt(this.selectedTripId);
                if (curId > 1) {
                    this.selectTrip(curId - 1);
                }
            },

            nextTrip() {
                if (this.selectedTripId === 'all') {
                    if (this.trips.length > 0) this.selectTrip(1);
                    return;
                }
                const curId = parseInt(this.selectedTripId);
                if (curId < this.trips.length) {
                    this.selectTrip(curId + 1);
                }
            },

            selectTrip(tripId) {
                this.stopAnimation();
                this.selectedTripId = (tripId === 'all') ? 'all' : parseInt(tripId);

                if (this.selectedTripId === 'all') {
                    this.renderAllPaths();
                    return;
                }

                const trip = this.trips.find(t => t.id === this.selectedTripId);
                if (!trip) return;

                this.clearLayers();
                const latlngs = trip.path.map(p => [p[0], p[1]]);

                const poly = L.polyline(latlngs, {
                    color: '#f59e0b',
                    weight: 5,
                    opacity: 0.95
                }).addTo(leafletMap);
                polylines.push(poly);

                if (latlngs.length > 0) {
                    const startPin = L.circleMarker(latlngs[0], {
                        radius: 7,
                        fillColor: '#10b981',
                        color: '#fff',
                        weight: 2,
                        fillOpacity: 1
                    }).addTo(leafletMap).bindPopup('จุดเริ่มต้นทริป');

                    const endPin = L.circleMarker(latlngs[latlngs.length - 1], {
                        radius: 7,
                        fillColor: '#ef4444',
                        color: '#fff',
                        weight: 2,
                        fillOpacity: 1
                    }).addTo(leafletMap).bindPopup('จุดสิ้นสุดทริป');

                    polylines.push(startPin, endPin);
                    leafletMap.fitBounds(L.latLngBounds(latlngs), { padding: [50, 50] });
                }
            },

            focusLocation(lat, lng, zoom = 15) {
                if (leafletMap) {
                    leafletMap.setView([lat, lng], zoom, { animate: true });
                }
            },

            resetView() {
                this.selectTrip('all');
            },

            toggleLifetimeOverview() {
                this.showLifetimeOverview = !this.showLifetimeOverview;
                if (this.showLifetimeOverview) {
                    this.renderLifetimeOverview();
                } else {
                    this.clearLifetimeOverview();
                }
            },

            renderLifetimeOverview() {
                this.clearLifetimeOverview();
                if (!leafletMap || !this.lifetimeTrips || this.lifetimeTrips.length === 0) return;

                const canvasRenderer = L.canvas({ padding: 0.5 });
                const layerGroup = L.layerGroup();

                const modeColors = {
                    'MOTORCYCLING': '#f59e0b',
                    'IN_PASSENGER_VEHICLE': '#38bdf8',
                    'WALKING': '#10b981',
                    'CYCLING': '#a855f7'
                };

                const allBounds = [];

                this.lifetimeTrips.forEach(tr => {
                    if (!tr.s || !tr.e) return;
                    const color = modeColors[tr.m] || '#f59e0b';
                    const poly = L.polyline([tr.s, tr.e], {
                        renderer: canvasRenderer,
                        color: color,
                        weight: 2,
                        opacity: 0.35
                    });

                    poly.bindPopup(`
                        <div class="p-1 space-y-0.5 text-xs">
                            <div class="font-bold text-amber-400">ทริปวันที่ ${tr.t}</div>
                            <div class="text-slate-200">ระยะทาง: <strong>${tr.d} กม.</strong></div>
                            <div class="text-[11px] text-slate-400">${tr.m === 'MOTORCYCLING' ? '🛵 มอเตอร์ไซค์' : (tr.m === 'IN_PASSENGER_VEHICLE' ? '🚗 รถยนต์' : '🚶 เดิน')}</div>
                        </div>
                    `);

                    layerGroup.addLayer(poly);
                    allBounds.push(tr.s, tr.e);
                });

                layerGroup.addTo(leafletMap);
                this.lifetimeLayerGroup = layerGroup;

                if (allBounds.length > 0) {
                    leafletMap.fitBounds(L.latLngBounds(allBounds), { padding: [30, 30] });
                }
            },

            clearLifetimeOverview() {
                if (this.lifetimeLayerGroup && leafletMap) {
                    leafletMap.removeLayer(this.lifetimeLayerGroup);
                    this.lifetimeLayerGroup = null;
                }
            },

            toggleAnimation() {
                if (this.isAnimating) {
                    this.stopAnimation();
                } else {
                    this.startAnimation();
                }
            },

            startAnimation() {
                let activeTrip = this.getActiveTrip() || this.trips[0];
                if (activeTrip && this.selectedTripId !== activeTrip.id) {
                    this.selectTrip(activeTrip.id);
                }

                if (!activeTrip || !activeTrip.path || activeTrip.path.length === 0) return;

                const pts = activeTrip.path;
                this.isAnimating = true;
                let idx = 0;

                if (animMarker && leafletMap) {
                    leafletMap.removeLayer(animMarker);
                }

                const motoIcon = L.divIcon({
                    className: 'pulse-marker',
                    html: '<div style="background:#f59e0b; width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid white; box-shadow:0 0 12px rgba(245,158,11,0.9); font-size:14px;">🛵</div>',
                    iconSize: [26, 26],
                    iconAnchor: [13, 13]
                });

                animMarker = L.marker([pts[0][0], pts[0][1]], { icon: motoIcon }).addTo(leafletMap);

                animTimer = setInterval(() => {
                    if (idx >= pts.length) {
                        this.stopAnimation();
                        return;
                    }
                    const pt = pts[idx];
                    animMarker.setLatLng([pt[0], pt[1]]);
                    this.animSpeed = pt[2] || 0;
                    this.animStatus = `${activeTrip.title} (${Math.round((idx / pts.length) * 100)}%)`;

                    // Pan smoothly every 10 frames
                    if (idx % 10 === 0 && leafletMap) {
                        leafletMap.panTo([pt[0], pt[1]], { animate: true, duration: 0.5 });
                    }
                    idx++;
                }, 70);
            },

            stopAnimation() {
                this.isAnimating = false;
                if (animTimer) {
                    clearInterval(animTimer);
                    animTimer = null;
                }
                if (animMarker && leafletMap) {
                    leafletMap.removeLayer(animMarker);
                    animMarker = null;
                }
            },

            formatTime(isoStr) {
                if (!isoStr) return '';
                try {
                    const d = new Date(isoStr);
                    return d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
                } catch (e) {
                    return isoStr.substring(11, 16);
                }
            },

            async loadProvincesGeo() {
                if (this.provincesGeo) return this.provincesGeo;
                this.provincesLoading = true;
                try {
                    const res = await fetch("{{ asset('thailand_provinces.geojson') }}");
                    if (res.ok) {
                        this.provincesGeo = await res.json();
                    }
                } catch (e) {
                    console.error('Failed to load Thailand provinces GeoJSON', e);
                } finally {
                    this.provincesLoading = false;
                }
                return this.provincesGeo;
            },

            async loadAmphoesGeo() {
                if (this.amphoesGeo) return this.amphoesGeo;
                this.amphoesLoading = true;
                try {
                    const res = await fetch("{{ asset('thailand_amphoes.geojson') }}");
                    if (res.ok) {
                        const geo = await res.json();
                        this.amphoesGeo = geo;
                        const list = [];
                        for (const f of (geo.features || [])) {
                            const pr = f.properties || {};
                            list.push({
                                code: String(pr.amp_code || ''),
                                th: String(pr.amp_th || ''),
                                en: String(pr.amp_en || ''),
                                pro_code: String(pr.pro_code || ''),
                                pro_th: String(pr.pro_th || ''),
                                reg: String(pr.reg_royin || ''),
                                lat: Number(pr.lat || 13.75),
                                lon: Number(pr.lon || 100.5)
                            });
                        }
                        list.sort((a, b) => a.pro_th.localeCompare(b.pro_th, 'th') || a.th.localeCompare(b.th, 'th'));
                        this.amphoeCatalog = list;
                    }
                } catch (e) {
                    console.error('Failed to load Thailand amphoes GeoJSON', e);
                } finally {
                    this.amphoesLoading = false;
                }
                return this.amphoesGeo;
            },

            async setRegionMapMode(mode, shouldFit = true) {
                this.regionMapMode = mode;
                if (mode === 'off') {
                    this.clearRegionLayer();
                    this.renderAllPaths();
                    return;
                }
                if (!this.keepTripsInRegionMode) {
                    this.clearLayers();
                } else {
                    this.renderAllPaths();
                }
                await this.renderRegionLayer(shouldFit);
            },

            toggleKeepTripsInRegionMode() {
                this.keepTripsInRegionMode = !this.keepTripsInRegionMode;
                if (this.keepTripsInRegionMode) {
                    this.renderAllPaths();
                } else {
                    this.clearLayers();
                }
                if (regionGeoLayer) {
                    regionGeoLayer.bringToBack();
                }
            },

            clearRegionLayer() {
                if (regionGeoLayer && leafletMap) {
                    leafletMap.removeLayer(regionGeoLayer);
                    regionGeoLayer = null;
                }
            },

            fitThailandBounds() {
                if (!leafletMap) return;
                if (regionGeoLayer && typeof regionGeoLayer.getBounds === 'function') {
                    const b = regionGeoLayer.getBounds();
                    if (b && b.isValid()) {
                        leafletMap.fitBounds(b, { padding: [24, 24] });
                        return;
                    }
                }
                leafletMap.setView([13.2, 101.2], 6, { animate: true });
            },

            async renderRegionLayer(fitBounds = false) {
                if (!leafletMap || this.regionMapMode === 'off') {
                    this.clearRegionLayer();
                    return;
                }

                const pal = COLOR_PALETTES[this.colorScheme] || COLOR_PALETTES.emerald;
                let sourceGeo = null;

                if (this.regionMapMode === 'province') {
                    sourceGeo = await this.loadProvincesGeo();
                } else if (this.regionMapMode === 'amphoe') {
                    sourceGeo = await this.loadAmphoesGeo();
                }

                if (!sourceGeo || this.regionMapMode === 'off') return;

                this.clearRegionLayer();

                let featuresToRender = sourceGeo.features || [];
                if (this.regionMapMode === 'amphoe' && this.selectedProvinceFilter !== 'all') {
                    featuresToRender = featuresToRender.filter(
                        f => String(f.properties?.pro_code || '') === String(this.selectedProvinceFilter)
                    );
                }

                const fc = { type: 'FeatureCollection', features: featuresToRender };

                regionGeoLayer = L.geoJSON(fc, {
                    style: (feature) => this.styleRegionFeature(feature),
                    onEachFeature: (feature, layer) => {
                        const pr = feature.properties || {};
                        const isAmp = this.regionMapMode === 'amphoe';
                        const code = String(isAmp ? pr.amp_code : pr.pro_code);
                        const st = isAmp ? this.getAmphoeStatus(code) : this.getProvinceStatus(code);

                        const titleTh = isAmp ? `${pr.amp_th} (${pr.pro_th})` : pr.pro_th;
                        const statusHtml = st.visited
                            ? (st.isAuto
                                ? `<span style="color:${pal.autoBorder};font-weight:700;">🛰️ GPS บันทึกแล้ว (${st.meta?.count || 0} จุด)</span>`
                                : `<span style="color:${pal.manualBorder};font-weight:700;">📌 ปักหมุดว่าเคยไป</span>`)
                            : '<span style="color:#94a3b8;">⏳ ยังไม่เคยไป</span>';

                        layer.bindTooltip(
                            `<div class="font-bold text-slate-100">${titleTh}</div><div>${statusHtml}</div>`,
                            { sticky: true, direction: 'top', className: 'thailand-region-tooltip' }
                        );

                        layer.on('mouseover', () => {
                            layer.setStyle({ weight: 2.2, color: '#f8fafc', fillOpacity: st.visited ? 0.72 : 0.25 });
                        });
                        layer.on('mouseout', () => {
                            if (regionGeoLayer) regionGeoLayer.resetStyle(layer);
                        });

                        layer.on('click', (ev) => {
                            L.DomEvent.stopPropagation(ev);
                            if (this.paintMode) {
                                this.toggleRegionVisit(isAmp ? 'amphoe' : 'province', code);
                                return;
                            }
                            this.openRegionPopup(feature, ev.latlng, layer);
                        });
                    }
                }).addTo(leafletMap);

                regionGeoLayer.bringToBack();

                if (fitBounds) {
                    const b = regionGeoLayer.getBounds();
                    if (b && b.isValid()) {
                        leafletMap.fitBounds(b, { padding: [24, 24] });
                    }
                }
            },

            styleRegionFeature(feature, pal = null) {
                const activePal = pal || COLOR_PALETTES[this.colorScheme] || COLOR_PALETTES.emerald;
                const pr = feature.properties || {};
                const isAmp = this.regionMapMode === 'amphoe';
                const code = String(isAmp ? pr.amp_code : pr.pro_code);
                const st = isAmp ? this.getAmphoeStatus(code) : this.getProvinceStatus(code);

                if (st.visited) {
                    return {
                        fillColor: st.isAuto ? activePal.autoFill : activePal.manualFill,
                        fillOpacity: st.isAuto ? 0.48 : 0.42,
                        color: st.isAuto ? activePal.autoBorder : activePal.manualBorder,
                        weight: isAmp ? 1.2 : 1.6,
                        opacity: 0.9
                    };
                }

                return {
                    fillColor: '#1e293b',
                    fillOpacity: 0.14,
                    color: '#475569',
                    weight: isAmp ? 0.7 : 1.0,
                    opacity: 0.55
                };
            },

            refreshRegionStyles() {
                if (!regionGeoLayer) return;
                const pal = COLOR_PALETTES[this.colorScheme] || COLOR_PALETTES.emerald;
                regionGeoLayer.eachLayer((layer) => {
                    if (layer.feature) {
                        layer.setStyle(this.styleRegionFeature(layer.feature, pal));
                        const pr = layer.feature.properties || {};
                        const isAmp = this.regionMapMode === 'amphoe';
                        const code = String(isAmp ? pr.amp_code : pr.pro_code);
                        const st = isAmp ? this.getAmphoeStatus(code) : this.getProvinceStatus(code);
                        const titleTh = isAmp ? `${pr.amp_th} (${pr.pro_th})` : pr.pro_th;
                        const statusHtml = st.visited
                            ? (st.isAuto
                                ? `<span style="color:${pal.autoBorder};font-weight:700;">🛰️ GPS บันทึกแล้ว (${st.meta?.count || 0} จุด)</span>`
                                : `<span style="color:${pal.manualBorder};font-weight:700;">📌 ปักหมุดว่าเคยไป</span>`)
                            : '<span style="color:#94a3b8;">⏳ ยังไม่เคยไป</span>';
                        layer.setTooltipContent(`<div class="font-bold text-slate-100">${titleTh}</div><div>${statusHtml}</div>`);
                    }
                });
            },

            openRegionPopup(feature, latlng, layer) {
                const pr = feature.properties || {};
                const isAmp = this.regionMapMode === 'amphoe';
                const type = isAmp ? 'amphoe' : 'province';
                const code = String(isAmp ? pr.amp_code : pr.pro_code);
                const st = isAmp ? this.getAmphoeStatus(code) : this.getProvinceStatus(code);
                const regLabel = REGION_LABELS[pr.reg_royin] || pr.reg_royin || '';

                let statsLine = 'ยังไม่พบบันทึกการเดินทางในเขตนี้';
                if (st.isAuto && st.meta) {
                    statsLine = `🛰️ พบพิกัด GPS ${st.meta.count} จุด (ล่าสุด ${st.meta.last_date || '-'})`;
                } else if (st.isManual) {
                    statsLine = '📌 ปักหมุดด้วยตนเองว่าเคยไปเยือนแล้ว';
                }

                let ampProgressLine = '';
                if (!isAmp) {
                    const cat = this.provinceCatalog.find(p => p.code === code);
                    const totalA = cat ? cat.amp_total : 0;
                    const visA = this.visitedAmphoeCountForProvince(code);
                    if (totalA > 0) {
                        ampProgressLine = `<div class="text-[11px] text-slate-300">อำเภอที่เคยไป: <strong class="text-sky-300">${visA} / ${totalA}</strong></div>`;
                    }
                }

                const toggleText = st.visited
                    ? (st.isAuto ? '🙈 ซ่อนไฮไลต์จังหวัด/อำเภอนี้' : '✕ ยกเลิกปักหมุดว่าเคยไป')
                    : '✅ ปักหมุดว่าเคยไปที่นี่แล้ว';

                const drillBtn = !isAmp
                    ? `<button type="button" onclick="window.__journeyComponent?.drillDownProvince('${code}')" class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-500 px-2.5 py-1.5 text-[11px] font-bold text-white cursor-pointer mt-1">🔍 ดูรายอำเภอของ ${pr.pro_th}</button>`
                    : '';

                const html = `
                    <div class="p-1.5 space-y-1.5 min-w-[200px]">
                        <div class="font-bold text-sm text-amber-300">${isAmp ? pr.amp_th : pr.pro_th}</div>
                        <div class="text-[11px] text-slate-400">${isAmp ? `${pr.amp_en} • จ.${pr.pro_th}` : `${pr.pro_en} • ${regLabel}`}</div>
                        <div class="text-[11px] text-slate-200 pt-1 border-t border-slate-700">${statsLine}</div>
                        ${ampProgressLine}
                        <div class="pt-1 flex flex-col gap-1">
                            <button type="button" onclick="window.__journeyComponent?.toggleRegionVisit('${type}', '${code}', true)" class="w-full rounded-lg ${st.visited ? 'bg-slate-700 hover:bg-slate-600 text-slate-200' : 'bg-emerald-600 hover:bg-emerald-500 text-white'} px-2.5 py-1.5 text-[11px] font-bold cursor-pointer">${toggleText}</button>
                            ${drillBtn}
                        </div>
                    </div>
                `;

                if (regionGeoPopup) {
                    leafletMap.closePopup(regionGeoPopup);
                }
                regionGeoPopup = L.popup().setLatLng(latlng).setContent(html).openOn(leafletMap);
            },

            async toggleRegionVisit(type, code, closePopup = false) {
                const c = String(code);
                const isProvince = type === 'province';
                const statusObj = isProvince ? this.getProvinceStatus(c) : this.getAmphoeStatus(c);
                const rawAuto = statusObj.rawAuto;

                // Optimistic local state update
                if (isProvince) {
                    if (rawAuto) {
                        if (this.hiddenProvinces.includes(c)) {
                            this.hiddenProvinces = this.hiddenProvinces.filter(x => x !== c);
                        } else {
                            this.hiddenProvinces.push(c);
                            this.manualProvinces = this.manualProvinces.filter(x => x !== c);
                        }
                    } else {
                        if (this.manualProvinces.includes(c)) {
                            this.manualProvinces = this.manualProvinces.filter(x => x !== c);
                        } else {
                            this.manualProvinces.push(c);
                        }
                    }
                } else {
                    if (rawAuto) {
                        if (this.hiddenAmphoes.includes(c)) {
                            this.hiddenAmphoes = this.hiddenAmphoes.filter(x => x !== c);
                        } else {
                            this.hiddenAmphoes.push(c);
                            this.manualAmphoes = this.manualAmphoes.filter(x => x !== c);
                        }
                    } else {
                        if (this.manualAmphoes.includes(c)) {
                            this.manualAmphoes = this.manualAmphoes.filter(x => x !== c);
                        } else {
                            this.manualAmphoes.push(c);
                        }
                    }
                }

                this.refreshRegionStyles();
                if (closePopup && leafletMap) {
                    leafletMap.closePopup();
                }

                this.savingRegionCode = c;
                try {
                    const res = await fetch(window.__REGION_TOGGLE_URL__, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.__CSRF_TOKEN__
                        },
                        body: JSON.stringify({
                            type: type,
                            code: c,
                            is_auto: rawAuto
                        })
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if (data.regions) {
                            this.manualProvinces = (data.regions.manual_provinces || []).map(String);
                            this.manualAmphoes = (data.regions.manual_amphoes || []).map(String);
                            this.hiddenProvinces = (data.regions.hidden_provinces || []).map(String);
                            this.hiddenAmphoes = (data.regions.hidden_amphoes || []).map(String);
                            this.refreshRegionStyles();
                        }
                    }
                } catch (e) {
                    console.error('Failed to persist region toggle', e);
                } finally {
                    this.savingRegionCode = null;
                }
            },

            async saveColorScheme(scheme) {
                this.colorScheme = scheme;
                this.refreshRegionStyles();
                try {
                    await fetch(window.__REGION_TOGGLE_URL__, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.__CSRF_TOKEN__
                        },
                        body: JSON.stringify({ type: 'color', color_scheme: scheme })
                    });
                } catch (e) {
                    console.error('Failed to save color scheme', e);
                }
            },

            async drillDownProvince(proCode) {
                if (leafletMap) leafletMap.closePopup();
                this.selectedProvinceFilter = String(proCode);
                this.amphoeProvinceFilter = String(proCode);
                await this.setRegionMapMode('amphoe', true);
            },

            async onAmphoeProvinceFilterChange() {
                if (this.regionMapMode === 'amphoe') {
                    await this.renderRegionLayer(true);
                }
            },

            async switchExplorerTab(tab) {
                this.selectedRegionTab = tab;
                if (tab === 'amphoes' && !this.amphoesGeo) {
                    await this.loadAmphoesGeo();
                }
            },

            async flyToRegion(type, lat, lon, code, proCode = null) {
                if (type === 'province') {
                    this.selectedProvinceFilter = 'all';
                    await this.setRegionMapMode('province', false);
                    if (leafletMap) leafletMap.setView([lat, lon], 9, { animate: true });
                } else {
                    if (proCode) {
                        this.selectedProvinceFilter = String(proCode);
                    }
                    await this.setRegionMapMode('amphoe', false);
                    if (leafletMap) leafletMap.setView([lat, lon], 11, { animate: true });
                }
                const mapEl = document.getElementById('journey-map');
                if (mapEl) mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }));
    };

    if (window.Alpine) {
        registerComponent();
    } else {
        document.addEventListener('alpine:init', registerComponent);
    }
})();
</script>

<div x-data="journeyMap" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 space-y-6">

    {{-- Top Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689A1.125 1.125 0 0 0 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-100">บันทึกการเดินทาง (Life Journey & Map Timeline)</h1>
                    <p class="text-xs sm:text-sm text-slate-400">ประมวลผลพิกัด GPS, ประวัติเส้นทางชีวิต, และสถานที่สำคัญจาก Google Timeline</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($gpConnected)
                <div class="inline-flex items-center gap-2 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-xs font-semibold text-emerald-300">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Google Photos เชื่อมต่อแล้ว</span>
                    <form method="POST" action="{{ route('portfolio.photos.disconnect') }}" class="inline ml-1">
                        @csrf
                        <button type="submit" class="text-slate-400 hover:text-rose-400 text-xs ml-1 cursor-pointer" title="ยกเลิกการเชื่อมต่อ">✕</button>
                    </form>
                </div>
            @else
                <a href="{{ route('portfolio.photos.connect') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-sky-500/40 bg-sky-500/15 hover:bg-sky-500/25 px-3.5 py-2 text-xs sm:text-sm font-semibold text-sky-200 transition shadow-sm">
                    <span>📸 เชื่อมต่อ Google Photos</span>
                </a>
            @endif

            <button type="button" @click="showUploadModal = true"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800/90 hover:bg-slate-700 px-3.5 py-2 text-xs sm:text-sm font-semibold text-slate-200 transition cursor-pointer">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <span>นำเข้าไฟล์ Takeout/JSON</span>
            </button>
            <a href="{{ route('portfolio.fuel.index') }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 px-4 py-2 text-xs sm:text-sm font-bold text-slate-950 shadow-md transition">
                <span>⛽ ดูสถิติค่าน้ำมัน</span>
            </a>
        </div>
    </div>

    {{-- Scoreboard Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {{-- Total Distance --}}
        <div class="rounded-2xl border border-slate-800 bg-[#161922] p-4 sm:p-5 flex flex-col justify-between shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                <span>ระยะทางบันทึกสะสมชีวิต</span>
                <span class="text-indigo-400">LIFETIME</span>
            </div>
            <div class="mt-2 text-2xl sm:text-3xl font-black text-indigo-400">
                {{ number_format($summary['total_lifetime_distance_km'] ?? $summary['total_distance_km'] ?? 0, 1) }} <span class="text-xs font-semibold text-slate-400">กม.</span>
            </div>
            <div class="mt-2 pt-2 border-t border-slate-800 text-[11px] text-slate-400">
                {{ number_format($summary['total_lifetime_trips'] ?? 2216) }} ทริปสะสม (แสดง {{ count($trips) }} ทริปล่าสุด)
            </div>
        </div>

        {{-- GPS Points Count --}}
        <div class="rounded-2xl border border-slate-800 bg-[#161922] p-4 sm:p-5 flex flex-col justify-between shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                <span>จุดพิกัด GPS ละเอียด</span>
                <span class="text-emerald-400">POINTS</span>
            </div>
            <div class="mt-2 text-2xl sm:text-3xl font-black text-emerald-400">
                {{ number_format($summary['total_points'] ?? 0) }} <span class="text-xs font-semibold text-slate-400">จุด</span>
            </div>
            <div class="mt-2 pt-2 border-t border-slate-800 text-[11px] text-slate-400">
                สกัดจาก Galaxy S24 Ultra
            </div>
        </div>

        {{-- Primary Mode of Transport --}}
        <div class="rounded-2xl border border-slate-800 bg-[#161922] p-4 sm:p-5 flex flex-col justify-between shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                <span>พาหนะหลัก</span>
                <span class="text-amber-400">MODE</span>
            </div>
            <div class="mt-2 text-lg sm:text-xl font-black text-amber-400 flex items-center gap-2">
                <span>{{ $summary['primary_mode'] ?? '🛵 มอเตอร์ไซค์ (71.9%)' }}</span>
            </div>
            <div class="mt-2 pt-2 border-t border-slate-800 text-[11px] text-slate-400">
                สัดส่วนการเดินทางยอดนิยม
            </div>
        </div>

        {{-- Recorded Date Span --}}
        <div class="rounded-2xl border border-slate-800 bg-[#161922] p-4 sm:p-5 flex flex-col justify-between shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                <span>ช่วงเวลาที่บันทึก</span>
                <span class="text-sky-400">DATE</span>
            </div>
            <div class="mt-2 text-sm sm:text-base font-bold text-slate-200">
                {{ $formatThaiDate($summary['date_range']['start'] ?? null) }}
            </div>
            <div class="mt-1 pt-2 border-t border-slate-800 text-[11px] text-slate-400">
                ถึง {{ $formatThaiDate($summary['date_range']['end'] ?? null) }}
            </div>
        </div>
    </div>

    {{-- Thailand Conquest Summary Banner (77 จังหวัด • 928 อำเภอ) --}}
    <div class="rounded-2xl border border-slate-800 bg-[#161922] p-4 sm:p-5 shadow-sm space-y-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-base">🇹🇭</span>
                    <h2 class="text-sm sm:text-base font-bold text-slate-100">แผนที่พิชิตประเทศไทย (จังหวัด & อำเภอ)</h2>
                    <span class="rounded-full bg-emerald-500/15 border border-emerald-500/30 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-300"
                          x-text="'GPS จับได้ ' + visitedProvincesSummary.autoCnt + ' จว.'"></span>
                    <span x-show="visitedProvincesSummary.manualCnt > 0" x-cloak
                          class="rounded-full bg-teal-500/15 border border-teal-500/30 px-2.5 py-0.5 text-[11px] font-semibold text-teal-300"
                          x-text="'ปักหมุดเอง ' + visitedProvincesSummary.manualCnt + ' จว.'"></span>
                </div>
                <p class="text-xs text-slate-400">
                    ตรวจจับอัตโนมัติจากพิกัด GPS ในไทม์ไลน์ และคลิกปักหมุดจังหวัดหรืออำเภอที่เคยไปก่อนหน้าเพิ่มเติมได้ตลอดเวลา
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                {{-- Province Progress --}}
                <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-3.5 py-2 min-w-[165px]">
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>จังหวัดที่ไปเยือน</span>
                        <span class="font-bold text-emerald-400" x-text="visitedProvincesSummary.pct + '%'"></span>
                    </div>
                    <div class="mt-0.5 flex items-baseline gap-1.5">
                        <span class="text-lg font-black text-slate-100" x-text="visitedProvincesSummary.total"></span>
                        <span class="text-xs text-slate-400" x-text="'/ ' + totalProvincesCount + ' จังหวัด'"></span>
                    </div>
                    <div class="mt-1.5 h-1.5 w-full rounded-full bg-slate-800 overflow-hidden">
                        <div class="h-full rounded-full bg-emerald-500 transition-all duration-300"
                             :style="'width: ' + visitedProvincesSummary.pct + '%'"></div>
                    </div>
                </div>

                {{-- Amphoe Progress --}}
                <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-3.5 py-2 min-w-[165px]">
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>อำเภอ / เขต ที่พิชิต</span>
                        <span class="font-bold text-sky-400" x-text="visitedAmphoesSummary.pct + '%'"></span>
                    </div>
                    <div class="mt-0.5 flex items-baseline gap-1.5">
                        <span class="text-lg font-black text-slate-100" x-text="visitedAmphoesSummary.total"></span>
                        <span class="text-xs text-slate-400" x-text="'/ ' + totalAmphoesCount + ' อำเภอ'"></span>
                    </div>
                    <div class="mt-1.5 h-1.5 w-full rounded-full bg-slate-800 overflow-hidden">
                        <div class="h-full rounded-full bg-sky-500 transition-all duration-300"
                             :style="'width: ' + visitedAmphoesSummary.pct + '%'"></div>
                    </div>
                </div>

                {{-- Highlight Color Switcher --}}
                <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-3 py-2 flex flex-col justify-between gap-1.5">
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">สีไฮไลต์</span>
                    <div class="flex items-center gap-1.5">
                        <template x-for="(pal, key) in regionPalettes" :key="key">
                            <button type="button" @click="saveColorScheme(key)"
                                    :title="pal.name"
                                    :class="[pal.swatch, colorScheme === key ? 'ring-2 ring-white ring-offset-2 ring-offset-slate-900 scale-110' : 'opacity-55 hover:opacity-100']"
                                    class="h-4 w-4 rounded-full transition cursor-pointer"></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Regional Mini Breakdown Pills --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 pt-2 border-t border-slate-800/80">
            <template x-for="reg in regionalBreakdown" :key="reg.key">
                <button type="button"
                        @click="regionFilter = (regionFilter === reg.key ? 'all' : reg.key); selectedRegionTab = 'provinces'; document.getElementById('thailand-explorer-section')?.scrollIntoView({ behavior: 'smooth' })"
                        :class="regionFilter === reg.key ? 'border-emerald-500/60 bg-emerald-500/10' : 'border-slate-800 bg-slate-900/50 hover:border-slate-700'"
                        class="flex items-center justify-between rounded-xl border px-3 py-2 text-left transition cursor-pointer">
                    <div>
                        <div class="text-[11px] font-semibold text-slate-300" x-text="reg.label"></div>
                        <div class="text-[10px] text-slate-500" x-text="Math.round((reg.visited / reg.total) * 100) + '%'"></div>
                    </div>
                    <div class="text-xs font-black" :class="reg.visited > 0 ? 'text-emerald-400' : 'text-slate-500'"
                         x-text="reg.visited + '/' + reg.total"></div>
                </button>
            </template>
        </div>
    </div>

    {{-- Google Photos Test Preview Card (Shows when photos are pulled) --}}
    @if(!empty($gpPhotos))
        <div class="rounded-2xl border border-slate-800 bg-[#161922] p-4 sm:p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="text-base">📸</span>
                    <h3 class="text-sm font-bold text-slate-100">ภาพถ่ายล่าสุดจาก Google Photos (ทดสอบดึงข้อมูล 2 ภาพล่าสุด)</h3>
                </div>
                <a href="{{ route('portfolio.photos.connect') }}" class="text-xs text-sky-400 hover:text-sky-300 flex items-center gap-1 font-semibold">
                    <span>🔄 ซิงค์ภาพล่าสุดใหม่</span>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($gpPhotos as $p)
                    <div class="flex gap-3.5 rounded-xl border border-slate-800 bg-slate-900/60 p-3 hover:border-slate-700 transition">
                        <img src="{{ $p['thumbnailUrl'] }}" alt="{{ $p['filename'] }}" class="h-24 w-24 rounded-lg object-cover bg-slate-800 shrink-0 border border-slate-700">
                        <div class="flex-1 min-w-0 space-y-1 text-xs">
                            <div class="font-bold text-slate-200 truncate" title="{{ $p['filename'] }}">{{ $p['filename'] }}</div>
                            <div class="text-[11px] text-slate-400">
                                เวลาถ่าย: <span class="text-slate-300">{{ $p['creationTime'] ? \Carbon\Carbon::parse($p['creationTime'])->timezone('Asia/Bangkok')->format('d/m/Y H:i น.') : '-' }}</span>
                            </div>
                            @if(!empty($p['cameraModel']))
                                <div class="text-[11px] text-amber-400">
                                    อุปกรณ์: {{ $p['cameraMake'] ?? '' }} {{ $p['cameraModel'] }}
                                </div>
                            @endif
                            @if(!empty($p['productUrl']))
                                <a href="{{ $p['productUrl'] }}" target="_blank" rel="noopener" class="inline-block text-[11px] text-sky-400 hover:underline pt-1">
                                    เปิดดูใน Google Photos ↗
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Main Map and Journey Explorer Area --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Map Canvas (2 cols) --}}
        <div class="lg:col-span-2 space-y-3">
            <div class="rounded-2xl border border-slate-800 bg-[#161922] overflow-hidden shadow-xl">
                {{-- Modern Map Toolbar --}}
                <div class="flex flex-wrap items-center justify-between gap-3 p-3 bg-slate-900/95 border-b border-slate-800">
                    {{-- Left: Modern Trip Selector & Stepper --}}
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Dropdown --}}
                        <div class="relative min-w-[240px] sm:min-w-[310px]">
                            <select x-model="selectedTripId" @change="selectTrip(selectedTripId)"
                                    class="w-full appearance-none rounded-xl bg-slate-800/95 hover:bg-slate-800 border border-slate-700/80 px-3.5 py-2 pr-9 text-xs font-semibold text-slate-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 transition cursor-pointer shadow-sm">
                                <option value="all">🗺️ แสดงทุกเส้นทาง ({{ count($trips) }} ทริป • {{ number_format(collect($trips)->sum('distance_km'), 1) }} กม.)</option>
                                @foreach($trips as $t)
                                    <option value="{{ $t['id'] }}">
                                        #{{ $t['id'] }} {{ $t['title'] }} — {{ number_format($t['distance_km'], 2) }} กม.
                                    </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        {{-- Prev / Next Stepper Buttons --}}
                        <div class="flex items-center rounded-xl bg-slate-800 border border-slate-700/80 p-0.5 shadow-sm">
                            <button type="button" @click="prevTrip()" :disabled="selectedTripId === 'all' || parseInt(selectedTripId) <= 1"
                                    class="rounded-lg p-1.5 text-slate-300 hover:text-white hover:bg-slate-700/80 disabled:opacity-30 disabled:pointer-events-none transition cursor-pointer"
                                    title="ทริปก่อนหน้า">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <div class="h-4 w-px bg-slate-700"></div>
                            <button type="button" @click="nextTrip()" :disabled="selectedTripId === 'all' || parseInt(selectedTripId) >= trips.length"
                                    class="rounded-lg p-1.5 text-slate-300 hover:text-white hover:bg-slate-700/80 disabled:opacity-30 disabled:pointer-events-none transition cursor-pointer"
                                    title="ทริปถัดไป">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                        {{-- Reset to All Quick Button --}}
                        <button type="button" x-show="selectedTripId !== 'all'" @click="selectTrip('all')" x-cloak
                                class="rounded-xl border border-slate-700/80 bg-slate-800/80 hover:bg-slate-700 px-2.5 py-1.5 text-xs text-slate-300 hover:text-white transition cursor-pointer flex items-center gap-1 shadow-sm">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span>ดูทั้งหมด</span>
                        </button>
                    </div>

                    {{-- Right: Play Animation & Map Controls --}}
                    <div class="flex items-center gap-2">
                        {{-- One-click button to view all 2,216 lifetime trips --}}
                        <button type="button" @click="toggleLifetimeOverview()"
                                :class="showLifetimeOverview ? 'bg-indigo-600 text-white shadow-indigo-500/30 ring-1 ring-indigo-400 font-bold' : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700'"
                                class="rounded-xl px-3 py-2 text-xs flex items-center gap-1.5 transition cursor-pointer shadow-sm"
                                title="กดเพื่อแสดงโครงข่ายการเดินทางทั้งหมด 2,216 ทริปตลอดชีวิต">
                            <span>🌐</span>
                            <span x-text="showLifetimeOverview ? 'ซ่อนภาพรวม 2,216 ทริป' : 'แสดงทั้ง 2,216 ทริป'"></span>
                        </button>

                        <button type="button" @click="toggleAnimation()"
                                :class="isAnimating ? 'bg-rose-500 hover:bg-rose-600 text-white shadow-rose-500/30' : 'bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold shadow-amber-500/20'"
                                class="rounded-xl px-3.5 py-2 text-xs flex items-center gap-1.5 transition cursor-pointer shadow-md">
                            <span x-text="isAnimating ? '⏹ หยุดจำลอง' : '▶ เล่นจำลองเส้นทาง'"></span>
                        </button>
                        <button type="button" @click="resetView()"
                                class="rounded-xl border border-slate-700 bg-slate-800 hover:bg-slate-700 p-2 text-slate-300 hover:text-white transition cursor-pointer shadow-sm"
                                title="รีเซ็ตมุมมอง">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Thailand Scratch/Highlight Layer Toolbar --}}
                <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 bg-slate-950/70 border-b border-slate-800 text-xs">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-[11px] font-semibold text-slate-400 mr-1">🗺️ เลเยอร์แผนที่ไทย:</span>
                        <button type="button" @click="setRegionMapMode('off', false)"
                                :class="regionMapMode === 'off' ? 'bg-slate-700 text-white font-bold border-slate-500' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-slate-200'"
                                class="rounded-lg border px-2.5 py-1 text-[11px] transition cursor-pointer">
                            🛣️ ปิดขอบเขต
                        </button>
                        <button type="button" @click="setRegionMapMode('province', true)"
                                :class="regionMapMode === 'province' ? 'bg-emerald-600 text-white font-bold border-emerald-500' : 'bg-slate-900 text-slate-300 border-slate-800 hover:bg-slate-800'"
                                class="rounded-lg border px-2.5 py-1 text-[11px] transition cursor-pointer flex items-center gap-1">
                            <span>🇹🇭 จังหวัด (77)</span>
                            <span x-show="provincesLoading" x-cloak class="animate-spin">⏳</span>
                        </button>
                        <button type="button" @click="setRegionMapMode('amphoe', true)"
                                :class="regionMapMode === 'amphoe' ? 'bg-sky-600 text-white font-bold border-sky-500' : 'bg-slate-900 text-slate-300 border-slate-800 hover:bg-slate-800'"
                                class="rounded-lg border px-2.5 py-1 text-[11px] transition cursor-pointer flex items-center gap-1">
                            <span>📍 อำเภอ (<span x-text="totalAmphoesCount"></span>)</span>
                            <span x-show="amphoesLoading" x-cloak class="animate-spin">⏳</span>
                        </button>

                        {{-- Optional Province filter when in Amphoe mode --}}
                        <select x-show="regionMapMode === 'amphoe'" x-cloak
                                x-model="selectedProvinceFilter" @change="onAmphoeProvinceFilterChange()"
                                class="rounded-lg bg-slate-900 border border-slate-700 px-2 py-1 text-[11px] text-slate-200 focus:border-sky-500 focus:outline-none cursor-pointer">
                            <option value="all">ทุกจังหวัด (แสดงทั้งประเทศ)</option>
                            <template x-for="p in provinceCatalog" :key="p.code">
                                <option :value="p.code" x-text="p.th + ' (' + visitedAmphoeCountForProvince(p.code) + '/' + p.amp_total + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <div x-show="regionMapMode !== 'off'" x-cloak class="flex flex-wrap items-center gap-1.5">
                        <button type="button" @click="paintMode = !paintMode"
                                :class="paintMode ? 'bg-amber-500 text-slate-950 font-bold border-amber-400' : 'bg-slate-900 text-slate-300 border-slate-800 hover:bg-slate-800'"
                                class="rounded-lg border px-2.5 py-1 text-[11px] transition cursor-pointer"
                                title="เมื่อเปิด: คลิกพื้นที่บนแผนที่เพื่อปักหมุด/ยกเลิกทันที">
                            <span x-text="paintMode ? '🖌️ โหมดจิ้มปักหมุด: เปิด' : '🖌️ จิ้มปักหมุดไว'"></span>
                        </button>
                        <button type="button" @click="toggleKeepTripsInRegionMode()"
                                :class="keepTripsInRegionMode ? 'bg-indigo-600/80 text-white border-indigo-500' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-slate-200'"
                                class="rounded-lg border px-2.5 py-1 text-[11px] transition cursor-pointer">
                            <span x-text="keepTripsInRegionMode ? '✓ ซ้อนเส้นทาง GPS' : 'ซ้อนเส้นทาง GPS'"></span>
                        </button>
                        <button type="button" @click="fitThailandBounds()"
                                class="rounded-lg border border-slate-800 bg-slate-900 hover:bg-slate-800 px-2.5 py-1 text-[11px] text-slate-300 transition cursor-pointer">
                            🔍 ซูมทั้งไทย
                        </button>
                    </div>
                </div>

                {{-- Selected Trip Info Strip (Shows when a trip is selected) --}}
                <div x-show="getActiveTrip()" x-cloak
                     class="flex flex-wrap items-center justify-between gap-2 px-4 py-2 bg-slate-950/80 border-b border-slate-800 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-amber-400" x-text="getActiveTrip() ? getActiveTrip().title : ''"></span>
                        <span class="text-slate-600">•</span>
                        <span class="text-slate-300">ระยะทาง: <strong class="text-amber-300 font-semibold" x-text="getActiveTrip() ? (getActiveTrip().distance_km + ' กม.') : ''"></strong></span>
                        <span class="hidden sm:inline text-slate-600">•</span>
                        <span class="hidden sm:inline text-slate-300">ความเร็วสูงสุด: <strong class="text-emerald-400 font-semibold" x-text="getActiveTrip() ? (getActiveTrip().max_speed_kmh + ' กม./ชม.') : ''"></strong></span>
                    </div>
                    <div class="text-[11px] text-slate-400 flex items-center gap-2">
                        <span x-text="getActiveTrip() ? ('📍 ' + getActiveTrip().points_count + ' จุด GPS') : ''"></span>
                        <span class="text-slate-600">•</span>
                        <span x-text="getActiveTrip() ? (formatTime(getActiveTrip().start_time) + ' - ' + formatTime(getActiveTrip().end_time)) : ''"></span>
                    </div>
                </div>

                {{-- The Map element with inline explicit dimensions --}}
                <div id="journey-map" style="width: 100%; height: 560px; min-height: 560px; position: relative; z-index: 1;"></div>

                {{-- Live Animation Status Bar --}}
                <div x-show="isAnimating" x-cloak class="p-3 bg-slate-900 border-t border-slate-800 flex items-center justify-between text-xs text-slate-300">
                    <div class="flex items-center gap-2">
                        <span class="flex h-2.5 w-2.5 rounded-full bg-amber-400 pulse-marker"></span>
                        <span>กำลังจำลองเส้นทาง: <strong class="text-amber-400" x-text="animStatus"></strong></span>
                    </div>
                    <div>
                        ความเร็วขณะนั้น: <strong class="text-emerald-400" x-text="animSpeed + ' กม./ชม.'"></strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Trips & Milestones List (1 col) --}}
        <div class="space-y-4 flex flex-col" style="max-height: 642px;">
            {{-- Trips Accordion Card --}}
            <div class="rounded-2xl border border-slate-800 bg-[#161922] p-4 shadow-sm flex flex-col shrink-0"
                 style="height: 440px; max-height: 440px;">
                <div class="flex items-center justify-between pb-2 border-b border-slate-800 shrink-0">
                    <h3 class="text-sm font-bold text-slate-100 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                        <span>รายการทริปการเดินทาง</span>
                    </h3>
                    <span class="text-[11px] text-slate-400 font-mono" x-text="filteredTrips.length + ' / ' + trips.length + ' ทริป'"></span>
                </div>

                {{-- Date Filter Quick Pills --}}
                <div class="flex items-center gap-1.5 py-2 overflow-x-auto custom-scrollbar shrink-0 border-b border-slate-800/60">
                    <button type="button" @click="dateFilter = 'all'; renderAllPaths()"
                            :class="dateFilter === 'all' ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-800 text-slate-400 hover:text-slate-200'"
                            class="rounded-lg px-2.5 py-1 text-[11px] whitespace-nowrap transition cursor-pointer">
                        ทั้งหมด ({{ count($trips) }})
                    </button>
                    <button type="button" @click="dateFilter = 'today'; renderAllPaths()"
                            :class="dateFilter === 'today' ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-400 hover:text-slate-200'"
                            class="rounded-lg px-2.5 py-1 text-[11px] whitespace-nowrap transition cursor-pointer">
                        10 พ.ย. (วันนี้)
                    </button>
                    <button type="button" @click="dateFilter = '2026-11-09'; renderAllPaths()"
                            :class="dateFilter === '2026-11-09' ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-400 hover:text-slate-200'"
                            class="rounded-lg px-2.5 py-1 text-[11px] whitespace-nowrap transition cursor-pointer">
                        9 พ.ย.
                    </button>
                    <button type="button" @click="dateFilter = '2026-11-08'; renderAllPaths()"
                            :class="dateFilter === '2026-11-08' ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-400 hover:text-slate-200'"
                            class="rounded-lg px-2.5 py-1 text-[11px] whitespace-nowrap transition cursor-pointer">
                        8 พ.ย.
                    </button>
                    <button type="button" @click="dateFilter = '2026-11'; renderAllPaths()"
                            :class="dateFilter === '2026-11' ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-400 hover:text-slate-200'"
                            class="rounded-lg px-2.5 py-1 text-[11px] whitespace-nowrap transition cursor-pointer">
                        พ.ย. 69
                    </button>
                    <button type="button" @click="dateFilter = '2026-10'; renderAllPaths()"
                            :class="dateFilter === '2026-10' ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-400 hover:text-slate-200'"
                            class="rounded-lg px-2.5 py-1 text-[11px] whitespace-nowrap transition cursor-pointer">
                        ต.ค. 69
                    </button>
                    <button type="button" @click="dateFilter = 'earlier'; renderAllPaths()"
                            :class="dateFilter === 'earlier' ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-400 hover:text-slate-200'"
                            class="rounded-lg px-2.5 py-1 text-[11px] whitespace-nowrap transition cursor-pointer">
                        ส.ค. - ก.ย. 69
                    </button>
                </div>

                {{-- Scrollable Trips List with Strict Height --}}
                <div class="space-y-2 mt-2 pr-1.5 custom-scrollbar flex-1 min-h-0"
                     style="overflow-y: auto;">
                    <template x-for="t in filteredTrips" :key="t.id">
                        <div @click="selectTrip(t.id)"
                             :class="(parseInt(selectedTripId) === t.id) ? 'border-amber-500/70 bg-amber-500/10 ring-1 ring-amber-500/40' : 'border-slate-800 bg-slate-900/60 hover:border-slate-700 hover:bg-slate-900/90'"
                             class="rounded-xl border p-2.5 cursor-pointer transition space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-200" x-text="'#' + t.id + ' ' + t.title"></span>
                                <span class="text-xs font-black text-amber-400" x-text="t.distance_km + ' กม.'"></span>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <span>⏱ <span x-text="formatTime(t.start_time) + ' - ' + formatTime(t.end_time)"></span></span>
                                <span>⚡ <span x-text="t.max_speed_kmh + ' กม./ชม.'"></span></span>
                                <span>📍 <span x-text="t.points_count + ' จุด'"></span></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Key Places Card (Compact) --}}
            <div class="rounded-2xl border border-slate-800 bg-[#161922] p-3.5 shadow-sm space-y-2 shrink-0"
                 style="height: 185px; max-height: 185px;">
                <div class="flex items-center justify-between pb-1.5 border-b border-slate-800">
                    <h3 class="text-xs font-bold text-slate-100 flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        <span>สถานที่สำคัญประจำตัว</span>
                    </h3>
                </div>

                <div class="space-y-1.5 text-xs overflow-y-auto custom-scrollbar" style="max-height: 125px;">
                    <div class="flex items-center justify-between rounded-lg bg-slate-900/60 border border-slate-800/80 p-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm">🏠</span>
                            <div>
                                <div class="font-bold text-slate-200 text-xs">บ้าน (HOME)</div>
                                <div class="text-[10px] text-slate-400">พิกัด 13.8238, 100.5552</div>
                            </div>
                        </div>
                        <button type="button" @click="focusLocation(13.823862, 100.555256, 16)"
                                class="rounded bg-slate-800 hover:bg-slate-700 px-2 py-0.5 text-[10px] text-slate-300 cursor-pointer">
                            ส่องดู
                        </button>
                    </div>

                    <div class="flex items-center justify-between rounded-lg bg-slate-900/60 border border-slate-800/80 p-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm">💼</span>
                            <div>
                                <div class="font-bold text-slate-200 text-xs">ที่ทำงาน (WORK)</div>
                                <div class="text-[10px] text-slate-400">พิกัด 13.8190, 100.5575</div>
                            </div>
                        </div>
                        <button type="button" @click="focusLocation(13.819086, 100.557508, 16)"
                                class="rounded bg-slate-800 hover:bg-slate-700 px-2 py-0.5 text-[10px] text-slate-300 cursor-pointer">
                            ส่องดู
                        </button>
                    </div>

                    <div class="flex items-center justify-between rounded-lg bg-slate-900/60 border border-slate-800/80 p-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm">📍</span>
                            <div>
                                <div class="font-bold text-slate-200 text-xs">จุดแวะพัก & เดินทางไกล</div>
                                <div class="text-[10px] text-slate-400">ย่านดอนเมือง - ปทุมธานี</div>
                            </div>
                        </div>
                        <button type="button" @click="selectTrip(3)"
                                class="rounded bg-slate-800 hover:bg-slate-700 px-2 py-0.5 text-[10px] text-slate-300 cursor-pointer">
                            ดูทริป
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── THAILAND CONQUEST EXPLORER (77 PROVINCES & 928 AMPHOES) ── --}}
    <div id="thailand-explorer-section" class="rounded-2xl border border-slate-800 bg-[#161922] p-4 sm:p-5 shadow-sm space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <button type="button" @click="switchExplorerTab('provinces')"
                        :class="selectedRegionTab === 'provinces' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border border-slate-800'"
                        class="rounded-xl px-3.5 py-2 text-xs transition cursor-pointer">
                    🇹🇭 รายชื่อ 77 จังหวัด (<span x-text="visitedProvincesSummary.total"></span>)
                </button>
                <button type="button" @click="switchExplorerTab('amphoes')"
                        :class="selectedRegionTab === 'amphoes' ? 'bg-sky-600 text-white font-bold' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border border-slate-800'"
                        class="rounded-xl px-3.5 py-2 text-xs transition cursor-pointer flex items-center gap-1.5">
                    <span>📍 รายชื่ออำเภอ / เขต (<span x-text="visitedAmphoesSummary.total"></span>)</span>
                    <span x-show="amphoesLoading" x-cloak class="animate-spin">⏳</span>
                </button>
            </div>

            <div class="text-[11px] text-slate-400 flex items-center gap-3">
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500 inline-block"></span> GPS จับได้</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-teal-400 inline-block"></span> ปักหมุดเอง</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-slate-700 inline-block"></span> ยังไม่เคยไป</span>
            </div>
        </div>

        {{-- TAB 1: PROVINCES CHECKLIST --}}
        <div x-show="selectedRegionTab === 'provinces'" class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <input type="text" x-model="regionSearch" placeholder="🔍 ค้นหาจังหวัด (ไทย / English)..."
                       class="flex-1 min-w-[200px] rounded-xl bg-slate-900 border border-slate-800 px-3.5 py-2 text-xs text-slate-200 placeholder-slate-500 focus:border-emerald-500 focus:outline-none">
                <select x-model="regionFilter"
                        class="rounded-xl bg-slate-900 border border-slate-800 px-3 py-2 text-xs text-slate-200 focus:border-emerald-500 focus:outline-none cursor-pointer">
                    <option value="all">ทุกภูมิภาค (77 จังหวัด)</option>
                    <option value="Central">ภาคกลาง</option>
                    <option value="North">ภาคเหนือ</option>
                    <option value="NorthEast">ภาคอีสาน</option>
                    <option value="East">ภาคตะวันออก</option>
                    <option value="West">ภาคตะวันตก</option>
                    <option value="South">ภาคใต้</option>
                </select>
                <select x-model="statusFilter"
                        class="rounded-xl bg-slate-900 border border-slate-800 px-3 py-2 text-xs text-slate-200 focus:border-emerald-500 focus:outline-none cursor-pointer">
                    <option value="all">สถานะ: ทั้งหมด</option>
                    <option value="visited">เฉพาะที่เคยไปแล้ว</option>
                    <option value="unvisited">เฉพาะที่ยังไม่เคยไป</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 max-h-[380px] overflow-y-auto custom-scrollbar pr-1">
                <template x-for="p in filteredProvincesList" :key="p.code">
                    <div :class="getProvinceStatus(p.code).visited ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-slate-800 bg-slate-900/60 hover:border-slate-700'"
                         class="rounded-xl border p-3 flex flex-col justify-between gap-2 transition">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-slate-100 truncate" x-text="p.th"></div>
                                <div class="text-[10px] text-slate-400 truncate" x-text="p.en + ' • ' + (regionLabels[p.reg] || p.reg)"></div>
                            </div>
                            <span x-show="getProvinceStatus(p.code).isAuto"
                                  class="shrink-0 rounded-md bg-emerald-500/20 px-1.5 py-0.5 text-[10px] font-bold text-emerald-300"
                                  x-text="'🛰️ ' + (getProvinceStatus(p.code).meta?.count || 0)"></span>
                            <span x-show="!getProvinceStatus(p.code).isAuto && getProvinceStatus(p.code).isManual"
                                  class="shrink-0 rounded-md bg-teal-500/20 px-1.5 py-0.5 text-[10px] font-bold text-teal-300">📌 เคยไป</span>
                        </div>

                        <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1 border-t border-slate-800/80">
                            <span x-text="'อำเภอ: ' + visitedAmphoeCountForProvince(p.code) + '/' + p.amp_total"></span>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="drillDownProvince(p.code); document.getElementById('journey-map')?.scrollIntoView({ behavior: 'smooth', block: 'center' })"
                                        class="rounded bg-slate-800 hover:bg-slate-700 px-1.5 py-0.5 text-slate-300 cursor-pointer" title="เจาะดูอำเภอ">🔍 อำเภอ</button>
                                <button type="button" @click="flyToRegion('province', p.lat, p.lon, p.code)"
                                        class="rounded bg-slate-800 hover:bg-slate-700 px-1.5 py-0.5 text-slate-300 cursor-pointer" title="ดูบนแผนที่">🗺️</button>
                                <button type="button" @click="toggleRegionVisit('province', p.code)"
                                        :disabled="savingRegionCode === p.code"
                                        :class="getProvinceStatus(p.code).visited ? 'bg-emerald-600/80 hover:bg-emerald-500 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-300'"
                                        class="rounded px-2 py-0.5 font-semibold cursor-pointer transition"
                                        x-text="getProvinceStatus(p.code).visited ? '✓ ไปแล้ว' : '+ ปักหมุด'"></button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- TAB 2: AMPHOES CHECKLIST --}}
        <div x-show="selectedRegionTab === 'amphoes'" x-cloak class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <input type="text" x-model="amphoeSearch" placeholder="🔍 ค้นหาอำเภอ / เขต (เช่น จตุจักร, คลองลาน, หัวหิน)..."
                       class="flex-1 min-w-[220px] rounded-xl bg-slate-900 border border-slate-800 px-3.5 py-2 text-xs text-slate-200 placeholder-slate-500 focus:border-sky-500 focus:outline-none">
                <select x-model="amphoeProvinceFilter"
                        class="rounded-xl bg-slate-900 border border-slate-800 px-3 py-2 text-xs text-slate-200 focus:border-sky-500 focus:outline-none cursor-pointer">
                    <option value="all">ทุกจังหวัด (928 อำเภอ)</option>
                    <template x-for="p in provinceCatalog" :key="p.code">
                        <option :value="p.code" x-text="p.th + ' (' + visitedAmphoeCountForProvince(p.code) + '/' + p.amp_total + ')'"></option>
                    </template>
                </select>
                <select x-model="amphoeStatusFilter"
                        class="rounded-xl bg-slate-900 border border-slate-800 px-3 py-2 text-xs text-slate-200 focus:border-sky-500 focus:outline-none cursor-pointer">
                    <option value="all">สถานะ: ทั้งหมด</option>
                    <option value="visited">เฉพาะที่เคยไปแล้ว</option>
                    <option value="unvisited">เฉพาะที่ยังไม่เคยไป</option>
                </select>
            </div>

            <div x-show="amphoesLoading" class="py-8 text-center text-xs text-slate-400">
                ⏳ กำลังโหลดข้อมูลขอบเขต 928 อำเภอทั่วไทย...
            </div>

            <div x-show="!amphoesLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 max-h-[380px] overflow-y-auto custom-scrollbar pr-1">
                <template x-for="a in filteredAmphoesList" :key="a.code">
                    <div :class="getAmphoeStatus(a.code).visited ? 'border-sky-500/40 bg-sky-500/5' : 'border-slate-800 bg-slate-900/60 hover:border-slate-700'"
                         class="rounded-xl border p-3 flex flex-col justify-between gap-2 transition">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-slate-100 truncate" x-text="a.th"></div>
                                <div class="text-[10px] text-slate-400 truncate" x-text="'จ.' + a.pro_th + ' • ' + a.en"></div>
                            </div>
                            <span x-show="getAmphoeStatus(a.code).isAuto"
                                  class="shrink-0 rounded-md bg-sky-500/20 px-1.5 py-0.5 text-[10px] font-bold text-sky-300"
                                  x-text="'🛰️ ' + (getAmphoeStatus(a.code).meta?.count || 0)"></span>
                            <span x-show="!getAmphoeStatus(a.code).isAuto && getAmphoeStatus(a.code).isManual"
                                  class="shrink-0 rounded-md bg-teal-500/20 px-1.5 py-0.5 text-[10px] font-bold text-teal-300">📌 เคยไป</span>
                        </div>

                        <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1 border-t border-slate-800/80">
                            <span x-text="getAmphoeStatus(a.code).meta?.last_date ? ('ล่าสุด ' + getAmphoeStatus(a.code).meta.last_date) : 'คลิกเพื่อปักหมุด'"></span>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="flyToRegion('amphoe', a.lat, a.lon, a.code, a.pro_code)"
                                        class="rounded bg-slate-800 hover:bg-slate-700 px-1.5 py-0.5 text-slate-300 cursor-pointer" title="ดูบนแผนที่">🗺️</button>
                                <button type="button" @click="toggleRegionVisit('amphoe', a.code)"
                                        :disabled="savingRegionCode === a.code"
                                        :class="getAmphoeStatus(a.code).visited ? 'bg-sky-600/80 hover:bg-sky-500 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-300'"
                                        class="rounded px-2 py-0.5 font-semibold cursor-pointer transition"
                                        x-text="getAmphoeStatus(a.code).visited ? '✓ ไปแล้ว' : '+ ปักหมุด'"></button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <div x-show="amphoeCatalog.length > 120" class="text-[11px] text-slate-500 text-right">
                * แสดงสูงสุด 120 รายการต่อหน้า — พิมพ์ชื่ออำเภอหรือเลือกจังหวัดเพื่อกรองให้แม่นยำขึ้น
            </div>
        </div>
    </div>

    {{-- ── UPLOAD MODAL ── --}}
    <div x-show="showUploadModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="showUploadModal = false"
             class="w-full max-w-md rounded-2xl border border-slate-700 bg-[#161922] p-5 sm:p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-400">📤</span>
                    <h3 class="text-base font-bold text-slate-100">นำเข้าไฟล์ Google Takeout / JSON</h3>
                </div>
                <button type="button" @click="showUploadModal = false" class="text-slate-400 hover:text-slate-200 cursor-pointer">
                    ✕
                </button>
            </div>

            <form method="POST" action="{{ route('portfolio.journey.upload') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-300">
                        เลือกไฟล์ Takeout (.zip) หรือไฟล์ Timeline (.json)
                    </label>
                    <input type="file" name="takeout_file" required accept=".zip,.json"
                           class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer border border-slate-700 rounded-xl bg-slate-800 p-2">
                    <p class="text-[11px] text-slate-400">
                        รองรับไฟล์ `takeout-*.zip`, `Timeline Edits.json` หรือ `Timeline.json` จาก Google Maps
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="showUploadModal = false"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-400 hover:bg-slate-800 cursor-pointer">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-indigo-600 hover:bg-indigo-500 px-5 py-2 text-xs font-bold text-white shadow cursor-pointer">
                        เริ่มประมวลผล
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

</x-portfolio-layout>
