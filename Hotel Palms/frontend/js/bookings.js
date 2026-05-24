/**
 * Módulo de reservas.
 * Gestiona la publicación, creación, cancelación y exportación de reservas.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

const Bookings = {

    /** @type {Array} */
    bookings: [],

    /** @type {number} */
    currentPage: 1,

    /** @type {number} */
    totalPages: 1,

    /** @type {object} */
    filters: {},

    /**
     * Inicializar el módulo de reservas.
     */
    init() {
        this.bindEvents();
        this.loadBookings();
    },

    /**
     * Cargar reservas desde la API.
     */
    async loadBookings() {
        const body = document.getElementById('bookingsBody');
        if (!body) return;

        body.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                </td>
            </tr>
        `;

        try {
            const queryString = Filters.buildQueryString({
                ...this.filters,
                page: this.currentPage,
                limit: 10,
            });

            const response = await fetch(`/api/v1/bookings?${queryString}`);
            const data = await response.json();

            if (data.success) {
                this.bookings = data.data.data;
                this.totalPages = data.data.pagination.last_page;
                this.renderBookings();
                this.renderPagination('bookingsPagination', this.currentPage, this.totalPages);
            }
        } catch (error) {
            body.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        <i class="bi bi-wifi-off me-2"></i>Connection error
                    </td>
                </tr>
            `;
        }
    },

    /**
     * Renderizar tabla de reservas.
     */
    renderBookings() {
        const body = document.getElementById('bookingsBody');
        if (!body) return;

        if (this.bookings.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <p>No bookings found</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        body.innerHTML = this.bookings.map(booking => `
            <tr>
                <td><strong>${booking.confirmation_code}</strong></td>
                <td>
                    <div>
                        <strong>${booking.customer_name ||
                            `${booking.first_name} ${booking.last_name}`}</strong>
                        <br><small class="text-muted">${booking.email}</small>
                    </div>
                </td>
                <td>
                    <span class="badge bg-secondary">${booking.room_type.toUpperCase()}</span>
                    <br><small>Room ${booking.room_number}</small>
                </td>
                <td>${this.formatDate(booking.check_in)}</td>
                <td>${this.formatDate(booking.check_out)}</td>
                <td class="text-center">${booking.guests}</td>
                <td><strong>€${parseFloat(booking.total_price).toFixed(2)}</strong></td>
                <td>
                    <span class="badge-status ${booking.status}">
                        ${this.formatStatus(booking.status)}
                    </span>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        ${this.canCancel(booking) ? `
                            <button class="btn btn-sm btn-outline-danger btn-icon"
                                    onclick="Bookings.cancelBooking(${booking.id})"
                                    title="Cancel">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        ` : ''}
                        ${Auth.hasRole('admin') || Auth.hasRole('receptionist') ? `
                            <button class="btn btn-sm btn-outline-primary btn-icon"
                                    onclick="Bookings.viewBooking(${booking.id})"
                                    title="View details">
                                <i class="bi bi-eye"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `).join('');
    },

    /**
     * Crear una nueva reserva.
     */
    async createBooking() {
        const roomId = document.getElementById('bookingRoom').value;
        const checkIn = document.getElementById('checkInDate').value;
        const checkOut = document.getElementById('checkOutDate').value;
        const guests = parseInt(document.getElementById('bookingGuests').value);
        const requests = document.getElementById('bookingRequests').value.trim();

        // Validar
        if (!roomId || !checkIn || !checkOut) {
            Notification.error('Please fill in all required fields');
            return;
        }

        if (!Validations.validateDateRange(checkIn, checkOut, 'checkInError', 'checkOutError')) {
            return;
        }

        if (guests < 1) {
            Notification.error('At least 1 guest is required');
            return;
        }

        try {
            const response = await fetch('/api/v1/bookings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    room_id: roomId,
                    check_in: checkIn,
                    check_out: checkOut,
                    guests: guests,
                    special_requests: requests || undefined,
                }),
            });

            const data = await response.json();

            if (data.success) {
                Notification.success(
                    `Booking confirmed! Code: ${data.data.confirmation_code}`
                );
                bootstrap.Modal.getInstance(document.getElementById('bookingModal')).hide();
                this.loadBookings();
            } else {
                if (data.errors) {
                    Object.values(data.errors).forEach(msg => Notification.error(msg));
                } else {
                    Notification.error(data.message || 'Error creating booking');
                }
            }
        } catch (error) {
            Notification.error('Connection error');
        }
    },

    /**
     * Cancelar una reserva.
     *
     * @param {number} bookingId - ID de reserva.
     */
    cancelBooking(bookingId) {
        if (!confirm('Are you sure you want to cancel this booking?')) return;

        fetch(`/api/v1/bookings/${bookingId}/cancel`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Notification.success('Booking cancelled successfully');
                    this.loadBookings();
                } else {
                    Notification.error(data.message || 'Error cancelling booking');
                }
            })
            .catch(error => {
                Notification.error('Connection error');
            });
    },

    /**
     * Cargar las habitaciones disponibles en el formulario de reserva.
     *
     * @param {string} checkIn - Fecha de entrada
     * @param {string} checkOut - Fecha de salida.
     */
    async loadAvailableRooms(checkIn, checkOut) {
        if (!checkIn || !checkOut) return;

        const select = document.getElementById('bookingRoom');
        select.innerHTML = '<option value="">Loading rooms...</option>';

        try {
            const response = await fetch(
                `/api/v1/rooms/availability?check_in=${checkIn}&check_out=${checkOut}`
            );
            const data = await response.json();

            if (data.success) {
                select.innerHTML = '<option value="">Select a room...</option>' +
                    data.data.map(room => `
                        <option value="${room.id}" data-price="${room.price_per_night}"
                                data-capacity="${room.capacity}">
                            Room ${room.room_number} - ${room.room_type} -
                            €${parseFloat(room.price_per_night).toFixed(2)}/night
                            (Max ${room.capacity} guests)
                        </option>
                    `).join('');

                this.updatePriceSummary();
            }
        } catch (error) {
            select.innerHTML = '<option value="">Error loading rooms</option>';
        }
    },

    /**
     * Actualizar el resumen de precios en el formulario de reserva.
     */
    updatePriceSummary() {
        const checkIn = document.getElementById('checkInDate').value;
        const checkOut = document.getElementById('checkOutDate').value;
        const roomSelect = document.getElementById('bookingRoom');
        const selectedOption = roomSelect.options[roomSelect.selectedIndex];

        if (checkIn && checkOut && selectedOption && selectedOption.value) {
            const date1 = new Date(checkIn);
            const date2 = new Date(checkOut);
            const nights = Math.ceil((date2 - date1) / (1000 * 60 * 60 * 24));
            const pricePerNight = parseFloat(selectedOption.dataset.price);
            const total = nights * pricePerNight;

            document.getElementById('summaryNights').textContent = nights;
            document.getElementById('summaryPricePerNight').textContent = `€${pricePerNight.toFixed(2)}`;
            document.getElementById('summaryTotal').textContent = `€${total.toFixed(2)}`;
        }
    },

    /**
     * Formatear la fecha para su visualización.
     *
     * @param {string} date - Date string
     * @return {string} Formatted date
     */
    formatDate(date) {
        if (!date) return '-';
        const d = new Date(date);
        return d.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    },

    /**
     * Estado de formato para visualización.
     *
     * @param {string} status - Cadena de estado.
     * @return {string} Estado formateado.
     */
    formatStatus(status) {
        return status.charAt(0).toUpperCase() + status.slice(1);
    },

    /**
     * Compruebe si el usuario puede cancelar una reserva.
     *
     * @param {object} booking - Objeto de reserva.
     * @return {boolean}
     */
    canCancel(booking) {
        const isAdmin = Auth.hasRole('admin') || Auth.hasRole('receptionist');
        const isOwner = Auth.getUserId() === booking.user_id;
        const canCancelStatus = booking.status === 'pending' || booking.status === 'confirmed';

        return (isAdmin || (isOwner && canCancelStatus));
    },

    /**
     * Paginación de renderizado.
     *
     * @param {string} containerId - ID del elemento contenedor.
     * @param {number} currentPage - Página actual.
     * @param {number} totalPages - Páginas totales.
     */
    renderPagination(containerId, currentPage, totalPages) {
        const container = document.getElementById(containerId);
        if (!container || totalPages <= 1) {
            if (container) container.innerHTML = '';
            return;
        }

        let html = '<ul class="pagination justify-content-center">';

        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="Bookings.goToPage(${currentPage - 1}); return false;">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                 </li>`;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                            <a class="page-link" href="#"
                               onclick="Bookings.goToPage(${i}); return false;">${i}</a>
                         </li>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="Bookings.goToPage(${currentPage + 1}); return false;">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                 </li>`;

        html += '</ul>';
        container.innerHTML = html;
    },

    /**
     * Navegar a una página específica.
     *
     * @param {number} page - Número de página.
     */
    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        this.currentPage = page;
        this.loadBookings();
    },

    /**
     * Vincular los oyentes de eventos.
     */
    bindEvents() {
        // Formulario de filtro.
        Filters.init('bookingFilters', (filters) => {
            this.filters = filters;
            this.currentPage = 1;
            this.loadBookings();
        });

        // Nuevo botón de reserva.
        const newBookingBtn = document.getElementById('newBookingBtn');
        if (newBookingBtn) {
            newBookingBtn.addEventListener('click', () => {
                document.getElementById('bookingForm').reset();
                document.getElementById('summaryNights').textContent = '0';
                document.getElementById('summaryPricePerNight').textContent = '€0.00';
                document.getElementById('summaryTotal').textContent = '€0.00';

                // Establecer la fecha mínima en hoy.
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('checkInDate').min = today;
                document.getElementById('checkOutDate').min = today;

                const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
                modal.show();
            });
        }

        // Controladores de cambio de fecha para la carga de habitaciones.
        const checkInInput = document.getElementById('checkInDate');
        const checkOutInput = document.getElementById('checkOutDate');

        if (checkInInput) {
            checkInInput.addEventListener('change', () => {
                const checkIn = checkInInput.value;
                if (checkIn) {
                    checkOutInput.min = checkIn;
                    // Establecer el plazo de salida predeterminado en +1 día.
                    const nextDay = new Date(checkIn);
                    nextDay.setDate(nextDay.getDate() + 1);
                    checkOutInput.value = nextDay.toISOString().split('T')[0];
                }
                this.loadAvailableRooms(checkInInput.value, checkOutInput.value);
                this.updatePriceSummary();
            });
        }

        if (checkOutInput) {
            checkOutInput.addEventListener('change', () => {
                this.loadAvailableRooms(checkInInput.value, checkOutInput.value);
                this.updatePriceSummary();
            });
        }

        // Cambio de selección de habitación.
        const bookingRoom = document.getElementById('bookingRoom');
        if (bookingRoom) {
            bookingRoom.addEventListener('change', () => this.updatePriceSummary());
        }

        // Botón guardar reserva.
        const saveBookingBtn = document.getElementById('saveBookingBtn');
        if (saveBookingBtn) {
            saveBookingBtn.addEventListener('click', () => this.createBooking());
        }

        // Botones de exportación.
        const exportPdfBtn = document.getElementById('exportPdfBtn');
        const exportCsvBtn = document.getElementById('exportCsvBtn');

        if (exportPdfBtn) {
            exportPdfBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = '/api/v1/bookings/export/pdf';
            });
        }

        if (exportCsvBtn) {
            exportCsvBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = '/api/v1/bookings/export/csv';
            });
        }
    }
};