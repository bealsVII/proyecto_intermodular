/**
 * Módulo Calendario Interactivo.
 * Muestra la disponibilidad de habitaciones en una interfaz de calendario.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

const Calendar = {

    /** @type {Date} */
    currentDate: new Date(),

    /** @type {object} */
    bookings: {},

    /** @type {string} */
    selectedDate: null,

    /**
     * Inicializar calendario.
     *
     * @param {string} containerId - ID del elemento contenedor.
     * @param {object} options - Opciones de calendario.
     */
    init(containerId, options = {}) {
        this.containerId = containerId;
        this.onDateSelect = options.onDateSelect || null;
        this.onDateRangeSelect = options.onDateRangeSelect || null;
        this.render();
    },

    /**
     * Cargar reservas desde la API para marcarlas en el calendario.
     *
     * @return {Promise<void>}
     */
    async loadBookings() {
        try {
            const response = await fetch('/api/v1/bookings');
            const data = await response.json();

            if (data.success) {
                this.bookings = {};
                data.data.bookings.forEach(booking => {
                    if (booking.status === 'confirmed') {
                        const checkIn = booking.check_in;
                        const checkOut = booking.check_out;

                        // Marque cada día en el rango.
                        let currentDate = new Date(checkIn);
                        const endDate = new Date(checkOut);

                        while (currentDate < endDate) {
                            const dateKey = currentDate.toISOString().split('T')[0];
                            this.bookings[dateKey] = this.bookings[dateKey] || [];
                            this.bookings[dateKey].push(booking);
                            currentDate.setDate(currentDate.getDate() + 1);
                        }
                    }
                });
                this.render();
            }
        } catch (error) {
            console.error('Error loading calendar bookings:', error);
        }
    },

    /**
     * Renderiza el calendario.
     */
    render() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();

        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();
        const today = new Date();

        let html = `
            <div class="calendar-container">
                <div class="calendar-header">
                    <button class="btn btn-sm btn-outline-secondary"
                            onclick="Calendar.previousMonth()">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <h5 class="mb-0">${monthNames[month]} ${year}</h5>
                    <button class="btn btn-sm btn-outline-secondary"
                            onclick="Calendar.nextMonth()">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <div class="calendar-grid">
        `;

        // Encabezados del día.
        dayNames.forEach(day => {
            html += `<div class="calendar-day-header">${day}</div>`;
        });

        // Días del mes anterior.
        for (let i = firstDay - 1; i >= 0; i--) {
            const day = daysInPrevMonth - i;
            html += `<div class="calendar-day other-month">${day}</div>`;
        }

        // Días del mes actual.
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const dateKey = date.toISOString().split('T')[0];
            const isToday = date.toDateString() === today.toDateString();
            const isPast = date < new Date(today.getFullYear(), today.getMonth(), today.getDate());
            const hasBookings = this.bookings[dateKey] && this.bookings[dateKey].length > 0;
            const isSelected = this.selectedDate === dateKey;

            let classes = 'calendar-day';
            if (isToday) classes += ' today';
            if (isPast) classes += ' disabled';
            if (hasBookings) classes += ' has-bookings';
            if (isSelected) classes += ' bg-primary text-white';

            const title = hasBookings
                ? `title="${this.bookings[dateKey].length} booking(s)"`
                : '';

            html += `<div class="${classes}" ${title}
                      onclick="${isPast ? '' : `Calendar.selectDate('${dateKey}')`}">
                ${day}
            </div>`;
        }

        // Días del próximo mes.
        const totalCells = firstDay + daysInMonth;
        const remainingCells = 42 - totalCells; // 6 filas × 7 días.
        for (let i = 1; i <= remainingCells; i++) {
            html += `<div class="calendar-day other-month">${i}</div>`;
        }

        html += '</div></div>';
        container.innerHTML = html;
    },

    /**
     * Navegar al mes anterior.
     */
    previousMonth() {
        this.currentDate.setMonth(this.currentDate.getMonth() - 1);
        this.render();
    },

    /**
     * Navegar al mes siguiente.
     */
    nextMonth() {
        this.currentDate.setMonth(this.currentDate.getMonth() + 1);
        this.render();
    },

    /**
     * Seleccione una fecha.
     *
     * @param {string} date - Cadena de fecha (YYYY-MM-DD)
     */
    selectDate(date) {
        this.selectedDate = date;
        this.render();

        if (this.onDateSelect) {
            this.onDateSelect(date);
        }
    },

    /**
     * Ir a hoy.
     */
    goToToday() {
        this.currentDate = new Date();
        this.render();
    }
};