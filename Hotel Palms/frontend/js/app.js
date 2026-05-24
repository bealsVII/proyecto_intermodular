/**
 * Módulo de aplicación principal.
 * Inicializa todos los módulos y gestiona el enrutamiento de páginas.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

const App = {

    /** @type {string} */
    currentPage: '',

    /**
     * Inicia la aplicación.
     */
    init() {
        console.log('%c🏨 Hotel Palms', 'font-size: 20px; font-weight: bold; color: #2563eb;');
        console.log('%cDAW 2ºS - Project', 'font-size: 12px; color: #64748b;');

        // Inicia primero la autentificación.
        Auth.init();

        // Página actual.
        this.currentPage = this.getCurrentPage();

        // Carga la página
        this.loadPage(this.currentPage);

        // Maneja la navegación del navegador.
        window.addEventListener('popstate', () => {
            this.currentPage = this.getCurrentPage();
            this.loadPage(this.currentPage);
        });

        // Gestionar los clics en los enlaces de navegación.
        this.bindNavLinks();
    },

    /**
     * Obtener la página actual desde la URL.
     *
     * @return {string}
     */
    getCurrentPage() {
        const params = new URLSearchParams(window.location.search);
        return params.get('page') || 'dashboard';
    },

    /**
     * Carga la página.
     *
     * @param {string} page - Nombre de la página.
     */
    async loadPage(page) {
        const mainContent = document.getElementById('mainContent');
        if (!mainContent) return;

        // Muestra el estado de carga.
        mainContent.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        try {
            // Verifica la autenticación para las páginas protegidas.
            if (this.requiresAuth(page) && !Auth.isAuthenticated()) {
                window.location.href = '?page=login&redirect=' + page;
                return;
            }

            // Consulta la página de administración para acceder a ellas.
            if (this.requiresAdmin(page) && !Auth.hasRole('admin')) {
                Notification.error('You do not have permission to access this page');
                window.location.href = '?page=dashboard';
                return;
            }

            // Carga la página HTML.
            const response = await fetch(`views/${page}.html`);
            const html = await response.text();
            mainContent.innerHTML = html;

            // Inicializar módulos específicos de la página.
            this.initPageModule(page);

            // Actualizar enlace de navegación activo.
            this.updateActiveNav(page);

            // Desplazarse hacia arriba.
            window.scrollTo(0, 0);

        } catch (error) {
            mainContent.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                    <h3 class="mt-3">Page Not Found</h3>
                    <p class="text-muted">The page you're looking for doesn't exist.</p>
                    <a href="?page=dashboard" class="btn btn-primary">Go to Dashboard</a>
                </div>
            `;
        }
    },

    /**
     * Inicializar módulo específico de la página.
     *
     * @param {string} page - Nombre de la página.
     */
    initPageModule(page) {
        switch (page) {
            case 'dashboard':
                this.initDashboard();
                break;
            case 'habitaciones':
                Rooms.init();
                break;
            case 'reservas':
                Bookings.init();
                break;
            case 'usuarios':
                Users.init();
                break;
            default:
                break;
        }
    },

    /**
     * Inicia dashboard.
     */
    async initDashboard() {
        // Establecer la fecha de hoy.
        const todayEl = document.getElementById('todayDate');
        if (todayEl) {
            todayEl.textContent = new Date().toLocaleDateString('en-GB', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
        }

        // Carga estadísticas.
        if (Auth.isAuthenticated()) {
            try {
                // Cargar estadísticas.
                const statsResponse = await fetch('/api/v1/bookings/statistics');
                const statsData = await statsResponse.json();

                if (statsData.success) {
                    const stats = statsData.data;
                    document.getElementById('statTotalRooms').textContent =
                        stats.total_rooms || '-';
                    document.getElementById('statActiveBookings').textContent =
                        stats.current_occupancy || '-';
                    document.getElementById('statTotalRevenue').textContent =
                        `€${parseFloat(stats.total_revenue || 0).toFixed(2)}`;
                    document.getElementById('statOccupancy').textContent =
                        `${stats.occupancy_rate || 0}%`;
                }

                // Cargar reservas recientes.
                const bookingsResponse = await fetch('/api/v1/bookings?limit=5');
                const bookingsData = await bookingsResponse.json();

                if (bookingsData.success && bookingsData.data.bookings.length > 0) {
                    const tbody = document.getElementById('recentBookingsBody');
                    tbody.innerHTML = bookingsData.data.bookings.map(booking => `
                        <tr>
                            <td><strong>${booking.confirmation_code}</strong></td>
                            <td>${booking.customer_name ||
                                `${booking.first_name} ${booking.last_name}`}</td>
                            <td>Room ${booking.room_number}</td>
                            <td>${Bookings.formatDate(booking.check_in)}</td>
                            <td>
                                <span class="badge-status ${booking.status}">
                                    ${Bookings.formatStatus(booking.status)}
                                </span>
                            </td>
                            <td><strong>€${parseFloat(booking.total_price).toFixed(2)}</strong></td>
                        </tr>
                    `).join('');
                }

                // Cargar las reservas de hoy.
                const today = new Date().toISOString().split('T')[0];
                const todayResponse = await fetch(`/api/v1/bookings?check_in=${today}`);
                const todayData = await todayResponse.json();

                if (todayData.success && todayData.data.bookings.length > 0) {
                    const todayList = document.getElementById('todayBookings');
                    todayList.innerHTML = todayData.data.bookings.map(booking => `
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <strong>Room ${booking.room_number}</strong>
                                <span class="badge-status ${booking.status}">
                                    ${Bookings.formatStatus(booking.status)}
                                </span>
                            </div>
                            <small class="text-muted">
                                ${booking.customer_name || `${booking.first_name} ${booking.last_name}`}
                            </small>
                        </li>
                    `).join('');
                }

            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }
    },

    /**
     * Comprueba si la página requiere autenticación.
     *
     * @param {string} page - Nombre de la página.
     * @return {boolean}
     */
    requiresAuth(page) {
        const publicPages = ['login', 'register'];
        return !publicPages.includes(page);
    },

    /**
     * Comprueba si la página requiere rol de administrador.
     *
     * @param {string} page - Nombre de la página.
     * @return {boolean}
     */
    requiresAdmin(page) {
        const adminPages = ['users'];
        return adminPages.includes(page);
    },

    /**
     * Actualizar el enlace de navegación activo.
     *
     * @param {string} page - Página actual.
     */
    updateActiveNav(page) {
        document.querySelectorAll('.nav-link[data-page]').forEach(link => {
            link.classList.toggle('active', link.dataset.page === page);
        });
    },

    /**
     * Vincular clics en enlaces de navegación.
     */
    bindNavLinks() {
        document.querySelectorAll('.nav-link[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = link.dataset.page;
                window.location.href = `?page=${page}`;
            });
        });
    }
};

// Inicializa la aplicación cuando el DOM esté listo.
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});