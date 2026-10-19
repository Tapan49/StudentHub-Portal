<?php

require_once __DIR__ . '/auth.php';

// Ensure user is authenticated
if (!is_logged_in()) {
    header("Location: login.php?error=unauthorized");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Live Event Manager</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .toolbar-card {
            background: #ffffff;
            padding: 20px 24px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 24px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .search-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            flex: 1;
        }
        .search-group input, .search-group select {
            padding: 9px 14px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.92rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-group input:focus, .search-group select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.15);
        }
        .btn-live {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.92rem;
            transition: background 0.2s;
        }
        .btn-live:hover { background-color: #0056b3; }
        .btn-refresh {
            background-color: #6c757d;
            color: white;
            padding: 9px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-refresh:hover { background-color: #5a6268; }

        /* Events Grid */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .event-card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-top: 4px solid #007bff;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
        }
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
        }
        .event-title {
            font-size: 1.15rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-open { background: #d4edda; color: #155724; }
        .badge-closed { background: #f8d7da; color: #721c24; }
        .badge-cat { background: #e8f4fd; color: #0d47a1; }
        .event-meta {
            font-size: 0.86rem;
            color: #666;
            margin: 6px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .event-desc {
            font-size: 0.88rem;
            color: #444;
            margin: 10px 0 16px 0;
            line-height: 1.4;
            min-height: 2.8em;
        }
        .event-actions {
            border-top: 1px solid #f0f0f0;
            padding-top: 12px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn-sm-edit {
            background: #ffc107;
            color: #212529;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-sm-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            cursor: pointer;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: white;
            padding: 28px;
            border-radius: 8px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: modalFadeIn 0.2s ease-out;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h3 { margin: 0; font-size: 1.3rem; }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.4rem;
            cursor: pointer;
            color: #888;
        }
        .form-row { margin-bottom: 14px; }
        .form-row label { display: block; font-weight: bold; font-size: 0.86rem; margin-bottom: 4px; color: #333; }
        .form-row input, .form-row select, .form-row textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 0.9rem;
        }

        /* Toast Notifications */
        #toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 14px 22px;
            border-radius: 6px;
            color: white;
            font-weight: bold;
            font-size: 0.92rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 2000;
            display: none;
            animation: toastSlide 0.3s ease-out;
        }
        @keyframes toastSlide {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast-success { background-color: #28a745; }
        .toast-error { background-color: #dc3545; }

        /* Loading & Empty States */
        #loadingIndicator {
            text-align: center;
            padding: 40px;
            font-size: 1.1rem;
            color: #666;
            display: none;
        }
        #emptyState {
            text-align: center;
            padding: 50px 20px;
            background: white;
            border-radius: 8px;
            color: #777;
            display: none;
        }
    </style>
</head>
<body>
<div class="page-wrapper">
<br>

<div class="grid-container">
    <!-- Header -->
    <div class="flex-row">
        <div class="flex-col">
            <h1 style="margin-bottom: 4px;">LIVE EVENT MANAGER</h1>
            <p style="color: #666; margin-top: 0;">Asynchronous Single-Page Event CRUD with Live Search Debounce & Instant UI Updates</p>
        </div>
        <div class="flex-col" style="text-align: right;">
            <a href="admin_dashboard.php"><b>ADMIN DASHBOARD</b></a> &nbsp;|&nbsp;
            <a href="event_manage.php"><b>EVENT CRUD (STANDARD)</b></a>
        </div>
    </div>

    <!-- Live Toolbar (Search with Debounce & Category Filter) -->
    <div class="flex-row">
        <div class="flex-col">
            <div class="toolbar-card">
                <div class="search-group">
                    <!-- Live Search with 300ms Debounce -->
                    <input type="text" id="liveSearchInput" placeholder="🔍 Live search title, location, type..." style="min-width: 280px;">

                    <!-- Live Filter by Category -->
                    <select id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="Technical">Technical</option>
                        <option value="Cultural">Cultural</option>
                        <option value="Sports">Sports</option>
                        <option value="Academic">Academic</option>
                    </select>

                    <!-- Live Filter by Status -->
                    <select id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="Open">Open</option>
                        <option value="Closed">Closed</option>
                    </select>

                    <button class="btn-refresh" id="refreshBtn" title="Refresh Live Data">🔄 Refresh</button>
                </div>

                <div>
                    <button class="btn-live" id="openAddModalBtn">➕ Create New Event</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status / Loading Bar -->
    <div id="loadingIndicator">
        ⏳ Loading events asynchronously via Fetch API...
    </div>

    <!-- Empty State -->
    <div id="emptyState">
        <h3>No Events Found</h3>
        <p>No campus events match your active search or filter criteria.</p>
    </div>

    <!-- Dynamic Events Grid Container -->
    <div class="flex-row">
        <div class="flex-col">
            <div class="events-grid" id="eventsContainer">
                <!-- Dynamically populated via Fetch API -->
            </div>
        </div>
    </div>
</div>

</div>

<!-- Modal for Create / Edit Event -->
<div class="modal-overlay" id="eventModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Create New Event</h3>
            <button class="modal-close" id="closeModalBtn">&times;</button>
        </div>
        <form id="eventForm">
            <input type="hidden" id="formEventId" name="event_id" value="">

            <div class="form-row">
                <label for="formTitle">Event Title *</label>
                <input type="text" id="formTitle" name="title" required placeholder="e.g. AI Innovation Summit 2026">
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label for="formCategory">Category *</label>
                    <select id="formCategory" name="category" required>
                        <option value="Technical">Technical</option>
                        <option value="Cultural">Cultural</option>
                        <option value="Sports">Sports</option>
                        <option value="Academic">Academic</option>
                    </select>
                </div>
                <div>
                    <label for="formType">Event Type *</label>
                    <input type="text" id="formType" name="event_type" required placeholder="e.g. Hackathon, Workshop">
                </div>
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label for="formDate">Date & Time *</label>
                    <input type="datetime-local" id="formDate" name="event_date" required>
                </div>
                <div>
                    <label for="formFormat">Format *</label>
                    <select id="formFormat" name="format" required>
                        <option value="Offline">Offline</option>
                        <option value="Online">Online</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                </div>
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                <div>
                    <label for="formLocation">Venue / Location</label>
                    <input type="text" id="formLocation" name="location" placeholder="e.g. Main Auditorium or Zoom Link">
                </div>
                <div>
                    <label for="formStatus">Status *</label>
                    <select id="formStatus" name="status" required>
                        <option value="Open">Open</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <label for="formDesc">Event Description</label>
                <textarea id="formDesc" name="description" rows="3" placeholder="Brief overview of event details..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px;">
                <button type="button" class="btn-refresh" id="cancelModalBtn">Cancel</button>
                <button type="submit" class="btn-live" id="saveEventBtn">Save Event</button>
            </div>
        </form>
    </div>
</div>

<!-- Floating Toast Notification -->
<div id="toast"></div>

<script>

const API_URL = 'api/events_api.php';

const eventsContainer = document.getElementById('eventsContainer');
const loadingIndicator = document.getElementById('loadingIndicator');
const emptyState = document.getElementById('emptyState');
const liveSearchInput = document.getElementById('liveSearchInput');
const categoryFilter = document.getElementById('categoryFilter');
const statusFilter = document.getElementById('statusFilter');
const refreshBtn = document.getElementById('refreshBtn');

// Modal Elements
const eventModal = document.getElementById('eventModal');
const openAddModalBtn = document.getElementById('openAddModalBtn');
const closeModalBtn = document.getElementById('closeModalBtn');
const cancelModalBtn = document.getElementById('cancelModalBtn');
const eventForm = document.getElementById('eventForm');
const modalTitle = document.getElementById('modalTitle');
const formEventId = document.getElementById('formEventId');
const formTitle = document.getElementById('formTitle');
const formCategory = document.getElementById('formCategory');
const formType = document.getElementById('formType');
const formDate = document.getElementById('formDate');
const formFormat = document.getElementById('formFormat');
const formLocation = document.getElementById('formLocation');
const formStatus = document.getElementById('formStatus');
const formDesc = document.getElementById('formDesc');
const saveEventBtn = document.getElementById('saveEventBtn');

// Toast Element
const toast = document.getElementById('toast');

function showToast(message, isError = false) {
    toast.textContent = message;
    toast.className = isError ? 'toast-error' : 'toast-success';
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3500);
}

/**
 * 1. FETCH & RENDER EVENTS (GET)
 */
async function fetchEvents() {
    loadingIndicator.style.display = 'block';
    emptyState.style.display = 'none';

    const search = liveSearchInput.value.trim();
    const category = categoryFilter.value;
    const status = statusFilter.value;

    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (category) params.append('category', category);
    if (status) params.append('status', status);

    try {
        const response = await fetch(`${API_URL}?${params.toString()}`);
        const result = await response.json();

        loadingIndicator.style.display = 'none';

        if (result.success && result.data && result.data.events) {
            renderEvents(result.data.events);
        } else {
            showToast(result.message || 'Error fetching events', true);
        }
    } catch (error) {
        loadingIndicator.style.display = 'none';
        showToast('Network error while fetching events.', true);
        console.error(error);
    }
}

/**
 * Render Event Cards in DOM
 */
function renderEvents(events) {
    eventsContainer.innerHTML = '';

    if (events.length === 0) {
        emptyState.style.display = 'block';
        return;
    }

    emptyState.style.display = 'none';

    events.forEach(ev => {
        const card = document.createElement('div');
        card.className = 'event-card';
        card.id = `event-card-${ev.event_id}`;

        const formattedDate = new Date(ev.event_date).toLocaleString('en-US', {
            month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });

        const statusClass = (ev.status === 'Open') ? 'badge-open' : 'badge-closed';

        card.innerHTML = `
            <div>
                <div class="event-header">
                    <h3 class="event-title">${escapeHtml(ev.title)}</h3>
                    <span class="badge ${statusClass}">${escapeHtml(ev.status)}</span>
                </div>
                <div class="event-meta">
                    <span class="badge badge-cat">${escapeHtml(ev.category || 'General')}</span>
                    <span>• ${escapeHtml(ev.event_type || 'Event')}</span>
                    <span>• ${escapeHtml(ev.format)}</span>
                </div>
                <div class="event-meta">
                    📅 ${formattedDate}
                </div>
                <div class="event-meta">
                    📍 ${escapeHtml(ev.location || 'Location TBA')}
                </div>
                <div class="event-desc">
                    ${escapeHtml(ev.description || 'No detailed description provided.')}
                </div>
            </div>
            <div class="event-actions">
                <button class="btn-sm-edit" onclick="openEditModal(${ev.event_id})">✏️ Edit</button>
                <button class="btn-sm-delete" onclick="deleteEvent(${ev.event_id}, '${escapeJs(ev.title)}')">🗑 Delete</button>
            </div>
        `;

        eventsContainer.appendChild(card);
    });
}

/**
 * 2. LIVE SEARCH WITH DEBOUNCE (Intermediate Extension)
 */
let debounceTimer;
liveSearchInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        fetchEvents();
    }, 300); // 300ms debounce
});

// Category and Status Change Listeners
categoryFilter.addEventListener('change', fetchEvents);
statusFilter.addEventListener('change', fetchEvents);
refreshBtn.addEventListener('click', fetchEvents);

/**
 * 3. OPEN MODALS (ADD & EDIT)
 */
openAddModalBtn.addEventListener('click', () => {
    eventForm.reset();
    formEventId.value = '';
    modalTitle.textContent = 'Create New Event';
    saveEventBtn.textContent = 'Create Event';
    eventModal.classList.add('active');
});

function closeModal() {
    eventModal.classList.remove('active');
}
closeModalBtn.addEventListener('click', closeModal);
cancelModalBtn.addEventListener('click', closeModal);
window.addEventListener('click', (e) => {
    if (e.target === eventModal) closeModal();
});

async function openEditModal(eventId) {
    try {
        const response = await fetch(`${API_URL}?id=${eventId}`);
        const result = await response.json();

        if (result.success && result.data) {
            const ev = result.data;
            formEventId.value = ev.event_id;
            formTitle.value = ev.title;
            formCategory.value = ev.category || 'Technical';
            formType.value = ev.event_type;
            // Format datetime for input
            formDate.value = ev.event_date ? ev.event_date.replace(' ', 'T').substring(0, 16) : '';
            formFormat.value = ev.format;
            formLocation.value = ev.location || '';
            formStatus.value = ev.status || 'Open';
            formDesc.value = ev.description || '';

            modalTitle.textContent = `Edit Event (#${ev.event_id})`;
            saveEventBtn.textContent = 'Save Changes';
            eventModal.classList.add('active');
        } else {
            showToast(result.message || 'Failed to load event details.', true);
        }
    } catch (error) {
        showToast('Network error loading event data.', true);
        console.error(error);
    }
}

/**
 * 4. SUBMIT FORM ASYNCHRONOUSLY (CREATE OR UPDATE)
 */
eventForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const isEdit = Boolean(formEventId.value);
    const method = isEdit ? 'PUT' : 'POST';

    const payload = {
        event_id: formEventId.value ? parseInt(formEventId.value) : null,
        title: formTitle.value.trim(),
        category: formCategory.value,
        event_type: formType.value.trim(),
        event_date: formDate.value,
        format: formFormat.value,
        location: formLocation.value.trim(),
        status: formStatus.value,
        description: formDesc.value.trim()
    };

    saveEventBtn.disabled = true;
    saveEventBtn.textContent = 'Saving...';

    try {
        const response = await fetch(API_URL, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        saveEventBtn.disabled = false;
        saveEventBtn.textContent = isEdit ? 'Save Changes' : 'Create Event';

        if (result.success) {
            closeModal();
            showToast(result.message || (isEdit ? 'Event updated!' : 'Event created!'));
            // Refresh events asynchronously without full reload
            fetchEvents();
        } else {
            showToast(result.message || 'Validation error.', true);
        }
    } catch (error) {
        saveEventBtn.disabled = false;
        saveEventBtn.textContent = isEdit ? 'Save Changes' : 'Create Event';
        showToast('Network error while saving event.', true);
        console.error(error);
    }
});

/**
 * 5. DELETE EVENT ASYNCHRONOUSLY (DELETE)
 */
async function deleteEvent(eventId, title) {
    if (!confirm(`Are you sure you want to delete "${title}"? This operation cannot be undone.`)) {
        return;
    }

    try {
        const response = await fetch(`${API_URL}?id=${eventId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ event_id: eventId })
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message || 'Event deleted successfully.');
            // Smoothly remove card from DOM without reloading page
            const card = document.getElementById(`event-card-${eventId}`);
            if (card) {
                card.style.transition = 'opacity 0.3s, transform 0.3s';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    card.remove();
                    if (eventsContainer.children.length === 0) {
                        emptyState.style.display = 'block';
                    }
                }, 300);
            } else {
                fetchEvents();
            }
        } else {
            showToast(result.message || 'Could not delete event.', true);
        }
    } catch (error) {
        showToast('Network error while deleting event.', true);
        console.error(error);
    }
}

// Utility Helpers
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}

function escapeJs(str) {
    if (!str) return '';
    return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

// Initial Fetch on Page Load
fetchEvents();
</script>
</body>
</html>
