/**
 * Módulo de Filtros.
 * Gestiona el filtrado dinámico de habitaciones, reservas y usuarios.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

const Filters = {

    /** @type {object} */
    activeFilters: {},

    /** @type {number} */
    debounceTimer: null,

    /**
     * Inicialice los filtros con búsqueda con retardo.
     *
     * @param {string} formId - Filtrar ID de formulario.
     * @param {Function} callback - Función de devolución de llamada para ejecutar.
     */
    init(formId, callback) {
        const form = document.getElementById(formId);
        if (!form) return;

        // Manejar el envío del formulario.
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            this.activeFilters = this.collectFilters(form);
            callback(this.activeFilters);
        });

        // Reinicio de manejo.
        form.addEventListener('reset', () => {
            this.activeFilters = {};
            setTimeout(() => callback({}), 10);
        });

        // Búsqueda sin rebotes para entradas de texto.
        const searchText = form.querySelector('input[type="text"], input[type="search"]');
        if (searchText) {
            searchText.addEventListener('input', () => {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.activeFilters = this.collectFilters(form);
                    callback(this.activeFilters);
                }, 300);
            });
        }
    },

    /**
     * Recopilar todos los valores de filtro del formulario.
     *
     * @param {HTMLFormElement} form - Elemento de formulario.
     * @return {object} Valores de filtro.
     */
    collectFilters(form) {
        const filters = {};
        const formData = new FormData(form);

        for (const [key, value] of formData.entries()) {
            if (value) {
                filters[key] = value;
            }
        }

        return filters;
    },

    /**
     * Aplicar filtro de rango de precios.
     *
     * @param {number} minPrice - Precio mínimo.
     * @param {number} maxPrice - Precio máximo.
     * @param {Array} items - Elementos para filtrar.
     * @return {Array} Artículos filtrados.
     */
    filterByPriceRange(minPrice, maxPrice, items) {
        return items.filter(item => {
            const price = parseFloat(item.price_per_night) || 0;
            if (minPrice && price < minPrice) return false;
            if (maxPrice && price > maxPrice) return false;
            return true;
        });
    },

    /**
     * Aplicar filtro de texto de búsqueda.
     *
     * @param {string} searchTerm - Término de búsqueda.
     * @param {Array} items - Elementos para filtrar.
     * @param {Array} fields - Campos en los que buscar.
     * @return {Array} Artículos filtrados.
     */
    filterBySearch(searchTerm, items, fields) {
        if (!searchTerm) return items;

        const lowerSearch = searchTerm.toLowerCase();
        return items.filter(item => {
            return fields.some(field => {
                const value = item[field];
                return value && String(value).toLowerCase().includes(lowerSearch);
            });
        });
    },

    /**
     * Aplicar filtro de estado.
     *
     * @param {string} status - Valor de estado.
     * @param {Array} items - Elementos para filtrar.
     * @return {Array} Artículos filtrados.
     */
    filterByStatus(status, items) {
        if (!status) return items;
        return items.filter(item => item.status === status);
    },

    /**
     * Aplicar filtro de rango de fechas.
     *
     * @param {string} dateFrom - Fecha de inicio.
     * @param {string} dateTo - Fecha de finalización.
     * @param {Array} items - Elementos para filtrar.
     * @param {string} dateField - Campo que contiene la fecha.
     * @return {Array} Artículos filtrados.
     */
    filterByDateRange(dateFrom, dateTo, items, dateField = 'check_in') {
        return items.filter(item => {
            const itemDate = item[dateField];
            if (dateFrom && itemDate < dateFrom) return false;
            if (dateTo && itemDate > dateTo) return false;
            return true;
        });
    },

    /**
     * Construye la cadena de consulta a partir de los filtros.
     *
     * @param {object} filters - Objeto de filtro.
     * @return {string} Cadena de consulta.
     */
    buildQueryString(filters) {
        const params = new URLSearchParams();

        for (const [key, value] of Object.entries(filters)) {
            if (value !== '' && value !== null && value !== undefined) {
                params.append(key, value);
            }
        }

        return params.toString();
    }
};