/**
 * Módulo de Habitaciones.
 * Gestiona la publicación, creación, edición y eliminación de habitaciones.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

const Rooms = {

    /** @type {Array} */
    rooms: [],

    /** @type {number} */
    currentPage: 1,

    /** @type {number} */
    totalPages: 1,

    /** @type {object} */
    filters: {},

    /**
     * Inicializar el módulo de habitaciones.
     */
    init() {
        this.bindEvents();
        this.loadRooms();
    },

    /**
     * Cargar salas desde la API.
     */
    async loadRooms() {
        const grid = document.getElementById('roomsGrid');
        if (!grid) return;

        grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading rooms...</span>
                </div>
            </div>
        `;

        try {
            const queryString = Filters.buildQueryString({
                ...this.filters,
                page: this.currentPage,
                limit: 12,
            });

            const response = await fetch(`/api/v1/rooms?${queryString}`);
            const data = await response.json();

            if (data.success) {
                this.rooms = data.data.data;
                this.totalPages = data.data.pagination.last_page;
                this.renderRooms();
                this.renderPagination('roomsPagination', this.currentPage, this.totalPages);
            } else {
                grid.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <div class="empty-state">
                            <i class="bi bi-exclamation-triangle"></i>
                            <p>Error loading rooms</p>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            grid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="empty-state">
                        <i class="bi bi-wifi-off"></i>
                        <p>Connection error. Please try again.</p>
                    </div>
                </div>
            `;
        }
    },

    /**
     * Renderizar cuadrícula de habitaciones.
     */
    renderRooms() {
        const grid = document.getElementById('roomsGrid');
        if (!grid) return;

        if (this.rooms.length === 0) {
            grid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="empty-state">
                        <i class="bi bi-door-open"></i>
                        <p>No rooms found</p>
                        <a href="#" class="btn btn-primary btn-sm mt-2"
                           onclick="Rooms.clearFilters()">Clear Filters</a>
                    </div>
                </div>
            `;
            return;
        }

        const typeIcons = {
            single: 'bi-person',
            double: 'bi-people',
            triple: 'bi-people',
            suite: 'bi-star',
            family: 'bi-people-fill',
        };

        const typeColors = {
            single: 'bg-info',
            double: 'bg-primary',
            triple: 'bg-success',
            suite: 'bg-warning',
            family: 'bg-danger',
        };

        grid.innerHTML = this.rooms.map(room => `
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card room-card h-100">
                    <div class="position-relative">
                        <div class="card-img-top d-flex align-items-center justify-content-center"
                             style="background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                            <i class="bi ${typeIcons[room.room_type] || 'bi-door-open'}
                                      text-white" style="font-size: 3rem;"></i>
                        </div>
                        <span class="room-badge ${room.is_available ? 'available' : 'unavailable'}">
                            ${room.is_available ? 'Available' : 'Booked'}
                        </span>
                        <span class="room-badge ${typeColors[room.room_type]} text-white"
                              style="top: auto; bottom: 10px; right: 10px; font-size: 0.75rem;">
                            ${room.room_type.toUpperCase()}
                        </span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Room ${room.room_number}</h5>
                        <p class="card-text text-muted small">${room.description || 'No description'}</p>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <i class="bi bi-people text-muted me-1"></i>
                                <small>${room.capacity} guest${room.capacity > 1 ? 's' : ''}</small>
                            </div>
                            <div>
                                <i class="bi bi-building text-muted me-1"></i>
                                <small>Floor ${room.floor || 'N/A'}</small>
                            </div>
                        </div>
                        ${room.amenities && room.amenities.length > 0 ? `
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Amenities:</small>
                                <div class="d-flex flex-wrap gap-1">
                                    ${room.amenities.slice(0, 3).map(a =>
                                        `<span class="badge bg-light text-dark">${a}</span>`
                                    ).join('')}
                                    ${room.amenities.length > 3 ?
                                        `<span class="badge bg-light text-dark">+${room.amenities.length - 3}</span>`
                                        : ''}
                                </div>
                            </div>
                        ` : ''}
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="room-price">
                                €${parseFloat(room.price_per_night).toFixed(2)}
                                <span>/night</span>
                            </div>
                            <div class="d-flex gap-1">
                                ${Auth.hasRole('admin') ? `
                                    <button class="btn btn-sm btn-outline-secondary btn-icon"
                                            onclick="Rooms.editRoom(${room.id})" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-icon"
                                            onclick="Rooms.deleteRoom(${room.id})" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                ` : ''}
                                <a href="?page=bookings&action=new&room=${room.id}"
                                   class="btn btn-sm btn-primary btn-icon" title="Book">
                                    <i class="bi bi-cart-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    },

    /**
     * Editar una habitación.
     *
     * @param {number} roomId - ID de habitación
     */
    editRoom(roomId) {
        fetch(`/api/v1/rooms/${roomId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const room = data.data;
                    document.getElementById('roomId').value = room.id;
                    document.getElementById('roomNumber').value = room.room_number;
                    document.getElementById('roomType').value = room.room_type;
                    document.getElementById('roomPrice').value = room.price_per_night;
                    document.getElementById('roomCapacity').value = room.capacity;
                    document.getElementById('roomFloor').value = room.floor || '';
                    document.getElementById('roomDescription').value = room.description || '';
                    document.getElementById('roomModalTitle').textContent = 'Edit Room';

                    const modal = new bootstrap.Modal(document.getElementById('roomModal'));
                    modal.show();
                }
            })
            .catch(error => {
                Notification.error('Error loading room data');
            });
    },

    /**
     * Eliminar una habitación.
     *
     * @param {number} roomId - ID de habitación.
     */
    deleteRoom(roomId) {
        if (!confirm('Are you sure you want to delete this room?')) return;

        fetch(`/api/v1/rooms/${roomId}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Notification.success('Room deleted successfully');
                    this.loadRooms();
                } else {
                    Notification.error(data.message || 'Error deleting room');
                }
            })
            .catch(error => {
                Notification.error('Connection error');
            });
    },

    /**
     * Guardar espacio (crear o actualizar).
     */
    saveRoom() {
        const roomId = document.getElementById('roomId').value;
        const roomData = {
            room_number: document.getElementById('roomNumber').value.trim(),
            room_type: document.getElementById('roomType').value,
            price_per_night: parseFloat(document.getElementById('roomPrice').value),
            capacity: parseInt(document.getElementById('roomCapacity').value),
            floor: parseInt(document.getElementById('roomFloor').value) || null,
            description: document.getElementById('roomDescription').value.trim(),
        };

        // Validar.
        if (!roomData.room_number || !roomData.room_type || isNaN(roomData.price_per_night)) {
            Notification.error('Please fill in all required fields');
            return;
        }

        const isUpdate = !!roomId;
        const method = isUpdate ? 'PUT' : 'POST';
        const url = isUpdate
            ? `/api/v1/rooms/${roomId}`
            : '/api/v1/rooms';

        fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(roomData),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Notification.success(
                        isUpdate ? 'Room updated successfully' : 'Room created successfully'
                    );
                    bootstrap.Modal.getInstance(document.getElementById('roomModal')).hide();
                    this.loadRooms();
                } else {
                    if (data.errors) {
                        Object.values(data.errors).forEach(msg => Notification.error(msg));
                    } else {
                        Notification.error(data.message || 'Error saving room');
                    }
                }
            })
            .catch(error => {
                Notification.error('Connection error');
            });
    },

    /**
     * Borra todos los filtros y vuelve a cargar.
     */
    clearFilters() {
        this.filters = {};
        this.currentPage = 1;
        this.loadRooms();
    },

    /**
     * Paginación de renderizado.
     *
     * @param {string} containerId - ID del elemento contenedor.
     * @param {number} currentPage - Número de página actual.
     * @param {number} totalPages - Número total de páginas.
     */
    renderPagination(containerId, currentPage, totalPages) {
        const container = document.getElementById(containerId);
        if (!container || totalPages <= 1) {
            if (container) container.innerHTML = '';
            return;
        }

        let html = '<ul class="pagination justify-content-center">';

        // Botón anterior.
        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="Rooms.goToPage(${currentPage - 1}); return false;">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                 </li>`;

        // Números de página.
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                            <a class="page-link" href="#"
                               onclick="Rooms.goToPage(${i}); return false;">${i}</a>
                         </li>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Botón siguiente.
        html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="Rooms.goToPage(${currentPage + 1}); return false;">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                 </li>`;

        html += '</ul>';
        container.innerHTML = html;
    },

    /**
     * Navegar a una página específica.
     *
     * @param {number} page - Número de páginas.
     */
    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        this.currentPage = page;
        this.loadRooms();
    },

    /**
     * Vincular los oyentes de eventos.
     */
    bindEvents() {
        // Formulario de filtro.
        Filters.init('roomFilters', (filters) => {
            this.filters = filters;
            this.currentPage = 1;
            this.loadRooms();
        });

        // Botón agregar habitación.
        const addRoomBtn = document.getElementById('addRoomBtn');
        if (addRoomBtn) {
            addRoomBtn.addEventListener('click', () => {
                document.getElementById('roomForm').reset();
                document.getElementById('roomId').value = '';
                document.getElementById('roomModalTitle').textContent = 'Add Room';

                const modal = new bootstrap.Modal(document.getElementById('roomModal'));
                modal.show();
            });
        }

        // Botón guardar habitación.
        const saveRoomBtn = document.getElementById('saveRoomBtn');
        if (saveRoomBtn) {
            saveRoomBtn.addEventListener('click', () => this.saveRoom());
        }
    }
};