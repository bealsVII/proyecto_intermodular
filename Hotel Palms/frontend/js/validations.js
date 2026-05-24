/**
 * Validación de formularios del lado del cliente.
 * Valida los campos antes de enviarlos al servidor.
 *
 * @author Beatriz Lara Solana
 * @version 1.0.0
 */

const Validations = {

    /**
     * Validar el formato del correo electrónico.
     *
     * @param {string} email - Correo electrónico para validar.
     * @param {string} errorElementId - ID del elemento de visualización de errores.
     * @return {boolean}
     */
    validateEmail(email, errorElementId) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test(email);

        this.displayError(errorElementId, isValid
            ? ''
            : 'Please enter a valid email address');

        return isValid;
    },

    /**
     * Validar la seguridad de la contraseña.
     *
     * @param {string} password - Contraseña para validar.
     * @param {string} errorElementId - ID del elemento de visualización de error.
     * @param {boolean} checkStrength - Si se deben consultar las reglas de fuerza.
     * @return {boolean}
     */
    validatePassword(password, errorElementId, checkStrength = true) {
        if (!password) {
            this.displayError(errorElementId, 'Password is required');
            return false;
        }

        if (password.length < 8) {
            this.displayError(errorElementId, 'Password must be at least 8 characters');
            return false;
        }

        if (checkStrength) {
            const checks = [
                { regex: /[A-Z]/, message: 'at least one uppercase letter' },
                { regex: /[a-z]/, message: 'at least one lowercase letter' },
                { regex: /[0-9]/, message: 'at least one number' },
                { regex: /[@$!%*?&]/, message: 'at least one special character' },
            ];

            for (const check of checks) {
                if (!check.regex.test(password)) {
                    this.displayError(errorElementId,
                        `Password must contain ${check.message}`);
                    return false;
                }
            }
        }

        this.displayError(errorElementId, '');
        return true;
    },

    /**
     * Validar la confirmación de la contraseña.
     *
     * @param {string} password - Contraseña original.
     * @param {string} confirmPassword - Contraseña de confirmación.
     * @param {string} errorElementId - ID del elemento de visualización de error.
     * @return {boolean}
     */
    validatePasswordConfirmation(password, confirmPassword, errorElementId) {
        if (password !== confirmPassword) {
            this.displayError(errorElementId, 'Passwords do not match');
            return false;
        }
        this.displayError(errorElementId, '');
        return true;
    },

    /**
     * Validar el número de teléfono.
     *
     * @param {string} phone - Número de teléfono.
     * @param {string} errorElementId - ID del elemento de visualización de error.
     * @return {boolean}
     */
    validatePhone(phone, errorElementId) {
        if (!phone) {
            this.displayError(errorElementId, '');
            return true; // Campo opcional.
        }

        const phoneRegex = /^[+]?[\d\s()-]{9,15}$/;
        const isValid = phoneRegex.test(phone);

        this.displayError(errorElementId, isValid
            ? ''
            : 'Please enter a valid phone number');

        return isValid;
    },

    /**
     * Validar DNI/NIF.
     *
     * @param {string} dni - DNI para validar.
     * @param {string} errorElementId - ID del elemento de visualización de error.
     * @return {boolean}
     */
    validateDni(dni, errorElementId) {
        if (!dni) {
            this.displayError(errorElementId, '');
            return true; // Campo opcional.
        }

        const dniRegex = /^\d{8}[A-Za-z]$/;
        const isValid = dniRegex.test(dni);

        this.displayError(errorElementId, isValid
            ? ''
            : 'Please enter a valid DNI (12345678A)');

        return isValid;
    },

    /**
     * Validar campo obligatorio.
     *
     * @param {string} value - Valor de campo.
     * @param {string} fieldName - Nombre del campo para el mensaje de error.
     * @param {string} errorElementId - ID del elemento de visualización de error.
     * @return {boolean}
     */
    validateRequired(value, fieldName, errorElementId) {
        if (!value || !value.trim()) {
            this.displayError(errorElementId, `${fieldName} is required`);
            return false;
        }
        this.displayError(errorElementId, '');
        return true;
    },

    /**
     * Validar rango de fechas.
     *
     * @param {string} checkIn - Fecha de entrada (YYYY-MM-DD).
     * @param {string} checkOut - Fecha de salida (YYYY-MM-DD).
     * @param {string} checkInErrorId - ID del elemento de error de registro.
     * @param {string} checkOutErrorId - ID del elemento de error de pago.
     * @return {boolean}
     */
    validateDateRange(checkIn, checkOut, checkInErrorId, checkOutErrorId) {
        let isValid = true;
        const today = new Date().toISOString().split('T')[0];

        if (!checkIn || checkIn < today) {
            this.displayError(checkInErrorId,
                'Check-in date must be today or in the future');
            isValid = false;
        } else {
            this.displayError(checkInErrorId, '');
        }

        if (!checkOut) {
            this.displayError(checkOutErrorId, 'Check-out date is required');
            isValid = false;
        } else if (checkOut <= checkIn) {
            this.displayError(checkOutErrorId,
                'Check-out date must be after check-in date');
            isValid = false;
        } else {
            this.displayError(checkOutErrorId, '');
        }

        return isValid;
    },

    /**
     * Validar campo numérico.
     *
     * @param {string} value - Valor de campo.
     * @param {number} min - Valor mínimo.
     * @param {number} max - Valor máximo.
     * @param {string} fieldName - Nombre del campo.
     * @param {string} errorElementId - ID del elemento de visualización de error.
     * @return {boolean}
     */
    validateNumber(value, min, max, fieldName, errorElementId) {
        const num = parseFloat(value);

        if (isNaN(num)) {
            this.displayError(errorElementId, `${fieldName} must be a number`);
            return false;
        }

        if (num < min) {
            this.displayError(errorElementId,
                `${fieldName} must be at least ${min}`);
            return false;
        }

        if (num > max) {
            this.displayError(errorElementId,
                `${fieldName} must be at most ${max}`);
            return false;
        }

        this.displayError(errorElementId, '');
        return true;
    },

    /**
     * Mostrar mensaje de error de validación.
     *
     * @param {string} elementId - ID de elemento.
     * @param {string} message - Mensaje de error.
     */
    displayError(elementId, message) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = message;
            if (message) {
                element.style.display = 'block';
            } else {
                element.style.display = 'none';
            }
        }
    },

    /**
     * Validar el formulario completo y devolver todos los errores.
     *
     * @param {string} formId - ID de elemento de formulario.
     * @param {object} validators - Objeto de funciones de validación.
     * @return {object} Errores de validación.
     */
    validateForm(formId, validators) {
        const form = document.getElementById(formId);
        if (!form) return {};

        const errors = {};

        for (const [fieldName, validator] of Object.entries(validators)) {
            const field = form.querySelector(`[name="${fieldName}"], #${fieldName}`);
            if (field) {
                const result = validator(field.value);
                if (!result.valid) {
                    errors[fieldName] = result.message;
                }
            }
        }

        return errors;
    }
};