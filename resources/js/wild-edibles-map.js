const maps = new Map();

function markerContent(marker) {
    const element = document.createElement('div');
    element.setAttribute('aria-label', `${marker.label}: ${marker.name}`);
    element.textContent = marker.icon || '•';
    element.style.cssText = `display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:${marker.color};border:3px solid white;box-shadow:0 1px 4px #333;cursor:pointer;font-size:16px;line-height:1;`;
    return element;
}

function removeMarkers(state) {
    state.infoWindow.close();
    state.markerObjects.forEach((marker) => {
        marker.map = null;
    });
    state.markerObjects = [];
}

function addMarkers(state, markers) {
    state.markerObjects = markers.map((marker) => {
        const object = new state.AdvancedMarkerElement({
            map: state.map,
            position: { lat: marker.latitude, lng: marker.longitude },
            title: marker.name,
            content: markerContent(marker),
        });
        object.addListener('click', () => {
            state.infoWindow.setContent(`<div class="wild-edible-info-window" style="font-size:0.8rem;line-height:1.4;"><strong>${escapeHtml(marker.name)}</strong><br>${escapeHtml(marker.label)}<br>${escapeHtml(marker.location_name || '')}<br>${escapeHtml(marker.season)}<br><a href="${escapeHtml(marker.url)}" style="font-size:0.8rem;">View details</a></div>`);
            state.infoWindow.open({ map: state.map, anchor: object });
        });
        return object;
    });
}

async function createMap(container, markers) {
    if (!window.google?.maps || !container) return;

    const existingState = maps.get(container.id);
    if (existingState) {
        removeMarkers(existingState);
        addMarkers(existingState, markers);
        return;
    }

    const { Map } = await google.maps.importLibrary('maps');
    const { AdvancedMarkerElement } = await google.maps.importLibrary('marker');
    const center = { lat: Number(container.dataset.centerLat), lng: Number(container.dataset.centerLng) };
    const map = new Map(container, {
        center,
        zoom: Number(container.dataset.zoom || 9),
        mapId: container.dataset.mapId || 'DEMO_MAP_ID',
        streetViewControl: false,
        mapTypeControl: false,
    });
    const state = { map, markerObjects: [], infoWindow: new google.maps.InfoWindow(), AdvancedMarkerElement };
    maps.set(container.id, state);
    addMarkers(state, markers);
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function initWildEdibleMaps() {
    document.querySelectorAll('#wild-edibles-map, #wild-edible-detail-map').forEach((container) => createMap(container, JSON.parse(container.dataset.markers || '[]')));
}

function updateWildEdibleMarkers(markers) {
    const container = document.getElementById('wild-edibles-map');
    const state = maps.get('wild-edibles-map');
    if (!container) return;
    if (!state) {
        createMap(container, markers);
        return;
    }

    removeMarkers(state);
    addMarkers(state, markers);
}

function setWireValue(id, value) {
    const input = document.getElementById(id);
    if (!input) return;
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

async function initWildEdiblePicker() {
    const container = document.getElementById('wild-edible-picker');
    if (!window.google?.maps || !container) return;
    const { Map } = await google.maps.importLibrary('maps');
    const { AdvancedMarkerElement } = await google.maps.importLibrary('marker');
    const position = { lat: Number(container.dataset.latitude), lng: Number(container.dataset.longitude) };
    const map = new Map(container, { center: position, zoom: 14, mapId: container.dataset.mapId || 'DEMO_MAP_ID', streetViewControl: false, mapTypeControl: false });
    const marker = new AdvancedMarkerElement({ map, position, gmpDraggable: true, title: 'Selected location', content: markerContent({ label: 'Selected', name: 'location', color: '#53875F' }) });
    const update = (lat, lng) => { setWireValue('wild-edible-latitude', lat.toFixed(7)); setWireValue('wild-edible-longitude', lng.toFixed(7)); };
    map.addListener('click', (event) => { marker.position = event.latLng; update(event.latLng.lat(), event.latLng.lng()); });
    marker.addListener('dragend', () => { const p = marker.position; update(typeof p.lat === 'function' ? p.lat() : p.lat, typeof p.lng === 'function' ? p.lng() : p.lng); });
}

window.__wildEdiblesGoogleMapsReady = () => {
    initWildEdibleMaps();
    initWildEdiblePicker();
};
window.initWildEdibleMaps = initWildEdibleMaps;
window.initWildEdiblePicker = initWildEdiblePicker;
window.updateWildEdibleMarkers = updateWildEdibleMarkers;

if (window.__wildEdiblesGoogleMapsPending) {
    window.__wildEdiblesGoogleMapsReady();
}

document.addEventListener('livewire:init', () => {
    Livewire.on('wild-edibles-updated', (event) => updateWildEdibleMarkers(event.markers || []));
});
