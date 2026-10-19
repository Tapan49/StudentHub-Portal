document.addEventListener('DOMContentLoaded', () => {
    const eventsContainer = document.getElementById('events-container');
    const paginationControls = document.getElementById('pagination-controls');
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const sortSelect = document.getElementById('sortSelect');
    const loadingMessage = document.getElementById('loading-message');
    const errorMessage = document.getElementById('error-message');

    let allEvents = [];
    let filteredEvents = [];
    const itemsPerPage = 5;
    let currentPage = 1;

    // Fetch data using Fetch API, with localStorage caching
    async function fetchEvents() {
        try {
            const cachedData = localStorage.getItem('events_cache');
            if (cachedData) {
                console.log('Loaded from localStorage cache');
                allEvents = JSON.parse(cachedData);
                processEvents();
            }

            const response = await fetch('../data/events.json');
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            
            // If data changed or no cache, update everything
            if (JSON.stringify(data) !== cachedData) {
                console.log('Fetched fresh data from server');
                allEvents = data;
                localStorage.setItem('events_cache', JSON.stringify(data));
                processEvents();
            }
        } catch (error) {
            console.error('Fetch error:', error);
            if (allEvents.length === 0) {
                errorMessage.textContent = 'Failed to load events. Please try again later.';
            }
        } finally {
            loadingMessage.style.display = 'none';
        }
    }

    function processEvents() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedType = typeFilter.value;
        const sortBy = sortSelect.value;

        // 1. Search and Filter
        filteredEvents = allEvents.filter(event => {
            const matchesSearch = event.title.toLowerCase().includes(searchTerm) || event.location.toLowerCase().includes(searchTerm);
            const matchesType = selectedType === 'All' || event.type === selectedType;
            return matchesSearch && matchesType;
        });

        // 2. Sort
        filteredEvents.sort((a, b) => {
            if (sortBy === 'date-asc') {
                return new Date(a.date) - new Date(b.date);
            } else if (sortBy === 'date-desc') {
                return new Date(b.date) - new Date(a.date);
            } else if (sortBy === 'title-asc') {
                return a.title.localeCompare(b.title);
            } else if (sortBy === 'title-desc') {
                return b.title.localeCompare(a.title);
            }
        });

        // 3. Reset pagination and render
        currentPage = 1;
        renderPagination();
        renderEvents();
    }

    function renderEvents() {
        eventsContainer.innerHTML = '';
        
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const paginatedEvents = filteredEvents.slice(startIndex, endIndex);

        if (paginatedEvents.length === 0) {
            eventsContainer.innerHTML = '<p>No events found matching your criteria.</p>';
            return;
        }

        // Map array to HTML elements
        paginatedEvents.forEach(event => {
            const dateObj = new Date(event.date);
            const dateStr = dateObj.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            const timeStr = dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

            const row = document.createElement('div');
            row.className = 'flex-row';
            row.innerHTML = `
                <div class="flex-col">
                    <h3>${event.title} <span style="font-size: 0.8rem; background: #0056b3; color: white; padding: 2px 6px; border-radius: 4px; margin-left: 10px;">${event.type}</span></h3>
                    <p><strong>DATE:</strong> ${dateStr}, ${timeStr}</p>
                    <p><strong>FORMAT:</strong> ${event.format}, <strong>LOCATION:</strong> ${event.location}</p>
                </div>
            `;
            eventsContainer.appendChild(row);
        });
    }

    function renderPagination() {
        paginationControls.innerHTML = '';
        const totalPages = Math.ceil(filteredEvents.length / itemsPerPage);

        if (totalPages <= 1) return;

        // Prev Button
        const prevBtn = document.createElement('button');
        prevBtn.textContent = 'Prev';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderEvents();
                renderPagination();
            }
        });
        paginationControls.appendChild(prevBtn);

        // Page Numbers
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            if (i === currentPage) pageBtn.classList.add('active');
            pageBtn.addEventListener('click', () => {
                currentPage = i;
                renderEvents();
                renderPagination();
            });
            paginationControls.appendChild(pageBtn);
        }

        // Next Button
        const nextBtn = document.createElement('button');
        nextBtn.textContent = 'Next';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderEvents();
                renderPagination();
            }
        });
        paginationControls.appendChild(nextBtn);
    }

    // Event Listeners for controls
    searchInput.addEventListener('input', processEvents);
    typeFilter.addEventListener('change', processEvents);
    sortSelect.addEventListener('change', processEvents);

    // Initial Fetch
    fetchEvents();
});
