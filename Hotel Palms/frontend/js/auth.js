/**
 * Módulo de autenticación.
 * Gestiona el inicio de sesión, el registro, el cierre de sesión y la administración de sesiones.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

const Auth = {

    /** @type {object|null} */
    currentUser: null,

    /** @type {string} */
    apiBaseUrl: '',

    /**
     * Inicializa el módulo de autenticación.
     */
    init() {
        this.apiBaseUrl = '/api/v1';
        this.checkSession();
        this.bindEvents();
    },

    /**
     * Compruebe si el usuario tiene una sesión activa.
     */
    async checkSession() {
        try {
            const response = await this.apiRequest('/auth/me', 'GET');
            if (response.success && response.data?.user) {
                this.currentUser = response.data.user;
                this.updateUI();
            } else {
                this.currentUser = null;
                this.updateUI();
            }
        } catch (error) {
            this.currentUser = null;
            this.updateUI();
        }
    },

    /**
     * Gestionar el envío del formulario de inicio de sesión.
     *
     * @param {Event} event - Enviar evento.
     */
    async handleLogin(event) {
        event.preventDefault();

        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;

        // Validación del lado del cliente.
        const isEmailValid = Validations.validateEmail(email, 'emailError');
        const isPasswordValid = Validations.validatePassword(password, 'passwordError', false);

        if (!isEmailValid || !isPasswordValid) {
            return;
        }

        // Mostrar estado de carga.
        const loginBtn = document.getElementById('loginBtn');
        const spinner = document.getElementById('loginSpinner');
        loginBtn.disabled = true;
        spinner.classList.remove('d-none');

        try {
            const response = await this.apiRequest('/auth/login', 'POST', {
                email,
                password,
            });

            if (response.success) {
                this.currentUser = response.data.user;
                Notification.success('Login successful! Welcome back.');
                this.updateUI();

                // Redirigir al panel de control o a la página anterior.
                const redirect = new URLSearchParams(window.location.search).get('redirect');
                window.location.href = redirect ? `?page=${redirect}` : '?page=dashboard';
            } else {
                Notification.error(response.message || 'Login failed');
            }
        } catch (error) {
            Notification.error('Connection error. Please try again.');
        } finally {
            loginBtn.disabled = false;
            spinner.classList.add('d-none');
        }
    },

    /**
     * Manejar el envío del formulario de registro.
     *
     * @param {Event} event - Enviar evento.
     */
    async handleRegistration(event) {
        event.preventDefault();

        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const passwordConfirmation = document.getElementById('passwordConfirmation').value;
        const phone = document.getElementById('phone')?.value.trim() || '';
        const dni = document.getElementById('dni')?.value.trim() || '';

        // Validación del lado del cliente.
        const validations = [
            Validations.validateRequired(firstName, 'First name', 'firstNameError'),
            Validations.validateRequired(lastName, 'Last name', 'lastNameError'),
            Validations.validateEmail(email, 'emailError'),
            Validations.validatePassword(password, 'passwordError', true),
            Validations.validatePasswordConfirmation(password, passwordConfirmation, 'passwordConfirmationError'),
            Validations.validatePhone(phone, 'phoneError'),
            Validations.validateDni(dni, 'dniError'),
        ];

        if (validations.includes(false)) {
            return;
        }

        // Mostrar estado de carga.
        const registerBtn = document.getElementById('registerBtn');
        const spinner = document.getElementById('registerSpinner');
        registerBtn.disabled = true;
        spinner.classList.remove('d-none');

        try {
            const response = await this.apiRequest('/auth/register', 'POST', {
                first_name: firstName,
                last_name: lastName,
                email,
                password,
                password_confirmation: passwordConfirmation,
                phone: phone || undefined,
                dni: dni || undefined,
            });

            if (response.success) {
                Notification.success('Account created successfully! Please login.');
                setTimeout(() => {
                    window.location.href = '?page=login';
                }, 1500);
            } else {
                Notification.error(response.message || 'Registration failed');
            }
        } catch (error) {
            Notification.error('Connection error. Please try again.');
        } finally {
            registerBtn.disabled = false;
            spinner.classList.add('d-none');
        }
    },

    /**
     * Manejar el cierre de sesión.
     */
    async handleLogout() {
        try {
            await this.apiRequest('/auth/logout', 'POST');
        } catch (error) {
            // Continuar con el cierre de sesión incluso si falla la llamada a la API.
        }

        this.currentUser = null;
        this.updateUI();
        Notification.info('You have been logged out.');
        window.location.href = '?page=login';
    },

    /**
     * Actualizar la interfaz de usuario en función del estado de autenticación.
     */
    updateUI() {
        const authMenu = document.getElementById('authMenu');
        const userMenu = document.getElementById('userMenu');
        const adminElements = document.querySelectorAll('.admin-only');

        if (this.currentUser) {
            // Estado registrado.
            if (authMenu) authMenu.classList.add('d-none');
            if (userMenu) userMenu.classList.remove('d-none');

            document.getElementById('userDisplayName').textContent =
                `${this.currentUser.first_name} ${this.currentUser.last_name}`;
            document.getElementById('userEmail').textContent = this.currentUser.email;
            document.getElementById('userRole').textContent = this.currentUser.role;

            // Mostrar elementos exclusivos para administradores.
            const isAdmin = this.currentUser.role === 'admin';
            const isReceptionist = this.currentUser.role === 'receptionist' || isAdmin;

            adminElements.forEach(el => {
                el.style.display = (isAdmin || isReceptionist) ? '' : 'none';
            });

            // Mostrar botones de exportación para administrador/recepcionista.
            const exportPdfBtn = document.getElementById('exportPdfBtn');
            const exportCsvBtn = document.getElementById('exportCsvBtn');
            if (isReceptionist) {
                if (exportPdfBtn) exportPdfBtn.style.display = '';
                if (exportCsvBtn) exportCsvBtn.style.display = '';
            }

        } else {
            // Estado desconectado.
            if (authMenu) authMenu.classList.remove('d-none');
            if (userMenu) userMenu.classList.add('d-none');

            adminElements.forEach(el => {
                el.style.display = 'none';
            });
        }
    },

    /**
     * Vincular los oyentes de eventos.
     */
    bindEvents() {
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegistration(e));
        }

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleLogout();
            });
        }
    },

    /**
     * Realizar solicitud de API.
     *
     * @param {string} endpoint - Punto final API.
     * @param {string} method - Método HTTP.
     * @param {object} body - Cuerpo de la solicitud.
     * @return {Promise<object>}
     */
    async apiRequest(endpoint, method = 'GET', body = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
            },
        };

        if (body) {
            options.body = JSON.stringify(body);
        }

        const response = await fetch(`${this.apiBaseUrl}${endpoint}`, options);
        return response.json();
    },

    /**
     * Compruebe si el usuario está autenticado.
     *
     * @return {boolean}
     */
    isAuthenticated() {
        return this.currentUser !== null;
    },

    /**
     * Compruebe si el usuario tiene un rol específico.
     *
     * @param {string} role - Rol a comprobar.
     * @return {boolean}
     */
    hasRole(role) {
        return this.currentUser?.role === role;
    },

    /**
     * Obtener el ID del usuario actual.
     *
     * @return {number|null}
     */
    getUserId() {
        return this.currentUser?.id || null;
    }
};

// Exponer funciones globales para manejadores de eventos HTML.
function handleLogin(event) { Auth.handleLogin(event); }
function handleRegistration(event) { Auth.handleRegistration(event); }