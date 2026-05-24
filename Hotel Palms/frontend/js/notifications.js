/**
 * Sistema de Notificación Dinámica.
 * Muestra notificaciones emergentes para que el usuario pueda dar su opinión.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

const Notification = {
    /** @type {HTMLElement} */
    container: null,

    /**
     * Inicializar el contenedor de notificaciones.
     */
    init() {
        this.container = document.getElementById('notificationContainer');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'notificationContainer';
            this.container.className = 'position-fixed top-0 end-0 p-3';
            this.container.style.zIndex = '1050';
            document.body.appendChild(this.container);
        }
    },

    /**
     * Mostrar una notificación.
     *
     * @param {string} message - Mensaje de notificación.
     * @param {string} type - Tipo de notificación: éxito, error, advertencia, información.
     * @param {number} duration - Duración del ocultamiento automático en ms (0 = sin ocultamiento automático).
     */
    show(message, type = 'info', duration = 4000) {
        this.init();

        const icons = {
            success: 'bi-check-circle-fill',
            error: 'bi-exclamation-triangle-fill',
            warning: 'bi-exclamation-circle-fill',
            info: 'bi-info-circle-fill',
        };

        const notification = document.createElement('div');
        notification.className = `notification notification-${type} mb-2`;
        notification.setAttribute('role', 'alert');

        notification.innerHTML = `
            <div class="d-flex align-items-center p-3">
                <i class="bi ${icons[type] || icons.info} me-2 fs-5"></i>
                <div class="flex-grow-1">${this.escapeHtml(message)}</div>
                <button type="button" class="btn-close btn-close-sm ms-2"
                        aria-label="Close" onclick="this.closest('.notification').remove()"></button>
            </div>
            ${duration > 0 ? `<div class="progress" style="height: 3px;">
                <div class="progress-bar" style="width: 100%;
                    animation: shrink ${duration}ms linear forwards;"></div>
            </div>` : ''}
        `;

        // Agregar animación de reducción.
        if (duration > 0) {
            const style = document.createElement('style');
            style.textContent = `
                @keyframes shrink {
                    from { width: 100%; }
                    to { width: 0%; }
                }
            `;
            notification.appendChild(style);
        }

        this.container.appendChild(notification);

        // Eliminación automática.
        if (duration > 0) {
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease forwards';
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }
    },

    /**
     * Mostrar notificación de éxito.
     *
     * @param {string} message
     * @param {number} duration
     */
    success(message, duration = 4000) {
        this.show(message, 'success', duration);
    },

    /**
     * Mostrar notificación de error.
     *
     * @param {string} message
     * @param {number} duration
     */
    error(message, duration = 5000) {
        this.show(message, 'error', duration);
    },

    /**
     * Mostrar notificación de advertencia.
     *
     * @param {string} message
     * @param {number} duration
     */
    warning(message, duration = 4000) {
        this.show(message, 'warning', duration);
    },

    /**
     * Mostrar notificación de información.
     *
     * @param {string} message
     * @param {number} duration
     */
    info(message, duration = 3000) {
        this.show(message, 'info', duration);
    },

    /**
     * Escapar del código HTML para prevenir XSS.
     *
     * @param {string} text
     * @return {string}
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};