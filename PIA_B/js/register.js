// Variables globales
let photoFile = null;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    setupFormValidation();
    setupPhotoUpload();
    setupFormSubmission();
});

// Configurar validación del formulario
function setupFormValidation() {
    const form = document.getElementById('registerForm');
    const inputs = form.querySelectorAll('input, select');

    inputs.forEach(input => {
        if (input.type !== 'radio' && input.type !== 'file') {
            // Efectos de focus
            input.addEventListener('focus', () => {
                const formGroup = input.closest('.form-group');
                formGroup.classList.add('focused');
                input.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', () => {
                const formGroup = input.closest('.form-group');
                formGroup.classList.remove('focused');
                input.style.transform = 'translateY(0)';
                
                // Validar campo
                validateField(input);
            });

            // Validación en tiempo real
            input.addEventListener('input', () => {
                clearFieldError(input);
                if (input.value.trim()) {
                    const formGroup = input.closest('.form-group');
                    formGroup.classList.add('has-value');
                } else {
                    const formGroup = input.closest('.form-group');
                    formGroup.classList.remove('has-value');
                }
            });
        }
    });

    // Validación para radio buttons
    const radioGroups = form.querySelectorAll('input[type="radio"]');
    radioGroups.forEach(radio => {
        radio.addEventListener('change', () => {
            clearFieldError(radio);
        });
    });
}

// Configurar carga de foto
function setupPhotoUpload() {
    const photoInput = document.getElementById('photoInput');
    
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            handlePhotoUpload(this);
        });
    }
}

// Manejar carga de foto
function handlePhotoUpload(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validar tipo de archivo
        if (!file.type.startsWith('image/')) {
            showToast('❌ Por favor selecciona un archivo de imagen válido', 'error');
            return;
        }
        
        // Validar tamaño (2MB máximo)
        if (file.size > 2 * 1024 * 1024) {
            showToast('❌ La imagen debe ser menor a 2MB', 'error');
            return;
        }
        
        photoFile = file;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const uploadArea = input.parentElement.querySelector('.upload-area');
            uploadArea.innerHTML = `
                <img src="${e.target.result}" alt="Foto del estudiante" style="width: 100%; max-width: 200px; height: 200px; object-fit: cover; border-radius: 12px; margin-bottom: 1rem;">
                <p style="color: #10b981; font-weight: 600;">✅ Foto cargada correctamente</p>
                <span style="color: #6b7280; font-size: 0.9rem;">Haz clic para cambiar la foto</span>
            `;
        };
        reader.readAsDataURL(file);
        
        showToast('📷 Foto cargada correctamente', 'success');
    }
}

// Configurar envío del formulario
function setupFormSubmission() {
    const form = document.getElementById('registerForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            submitForm();
        }
    });
}

// Validar formulario completo
function validateForm() {
    const form = document.getElementById('registerForm');
    const inputs = form.querySelectorAll('input, select');
    let isValid = true;
    const errors = [];

    inputs.forEach(input => {
        if (input.type === 'file') return; // Skip file inputs
        
        clearFieldError(input);
        
        if (input.type === 'radio') {
            const radioGroup = form.querySelectorAll(`input[name="${input.name}"]`);
            const isRadioGroupValid = Array.from(radioGroup).some(radio => radio.checked);
            
            if (!isRadioGroupValid && input === radioGroup[0]) {
                isValid = false;
                errors.push('Debes seleccionar el género');
                showFieldError(input, 'Debes seleccionar una opción');
            }
        } else if (input.required && !input.value.trim()) {
            isValid = false;
            const fieldName = input.closest('.form-group').querySelector('label').textContent.replace(/[^\w\s]/gi, '');
            errors.push(`${fieldName} es requerido`);
            showFieldError(input, 'Este campo es requerido');
        } else if (input.value.trim()) {
            // Validaciones específicas
            if (!validateSpecificField(input)) {
                isValid = false;
            }
        }
    });

    if (!isValid && errors.length > 0) {
        showToast('❌ Por favor corrige los errores en el formulario', 'error');
    }

    return isValid;
}

// Validar campo específico
function validateField(input) {
    clearFieldError(input);
    
    if (input.required && !input.value.trim()) {
        showFieldError(input, 'Este campo es requerido');
        return false;
    }
    
    return validateSpecificField(input);
}

// Validaciones específicas por campo
function validateSpecificField(input) {
    const value = input.value.trim();
    
    switch (input.name) {
        case 'nombre':
        case 'apellido':
            if (value.length < 2) {
                showFieldError(input, 'Debe tener al menos 2 caracteres');
                return false;
            }
            if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(value)) {
                showFieldError(input, 'Solo se permiten letras');
                return false;
            }
            break;
            
        case 'documento':
            if (!/^\d{8,12}$/.test(value)) {
                showFieldError(input, 'Debe tener entre 8 y 12 dígitos');
                return false;
            }
            break;
            
        case 'telefono':
            if (!/^\d{7,10}$/.test(value.replace(/[-\s]/g, ''))) {
                showFieldError(input, 'Formato de teléfono inválido');
                return false;
            }
            break;
            
        case 'email':
            if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                showFieldError(input, 'Formato de email inválido');
                return false;
            }
            break;
            
        case 'fechaNacimiento':
            const birthDate = new Date(value);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            
            if (age < 5 || age > 25) {
                showFieldError(input, 'La edad debe estar entre 5 y 25 años');
                return false;
            }
            break;
    }
    
    // Marcar como válido
    const formGroup = input.closest('.form-group');
    formGroup.classList.add('success');
    
    return true;
}

// Mostrar error en campo
function showFieldError(input, message) {
    const formGroup = input.closest('.form-group');
    let errorElement = formGroup.querySelector('.error-message');

    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        formGroup.appendChild(errorElement);
    }

    errorElement.textContent = message;
    errorElement.classList.add('show');
    
    if (input.type !== 'radio') {
        input.style.borderColor = '#ef4444';
        input.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
    } else {
        const radioOptions = formGroup.querySelectorAll('.radio-option');
        radioOptions.forEach(option => {
            option.style.borderColor = '#ef4444';
        });
    }

    // Animación de shake
    formGroup.style.animation = 'shake 0.5s ease-in-out';
    setTimeout(() => {
        formGroup.style.animation = '';
    }, 500);
}

// Limpiar error de campo
function clearFieldError(input) {
    const formGroup = input.closest('.form-group');
    const errorElement = formGroup.querySelector('.error-message');

    if (errorElement) {
        errorElement.classList.remove('show');
        setTimeout(() => {
            if (errorElement.parentNode) {
                errorElement.remove();
            }
        }, 300);
    }

    if (input.type !== 'radio') {
        input.style.borderColor = '#e5e7eb';
        input.style.boxShadow = 'none';
    } else {
        const radioOptions = formGroup.querySelectorAll('.radio-option');
        radioOptions.forEach(option => {
            option.style.borderColor = '#e5e7eb';
        });
    }
    
    formGroup.classList.remove('error');
}

// Enviar formulario
function submitForm() {
    const form = document.getElementById('registerForm');
    const formData = new FormData(form);
    
    // Mostrar loading
    showLoadingState();
    
    // Simular proceso de registro
    setTimeout(() => {
        // Generar código único
        const codigo = generateUniqueCode();
        
        // Crear objeto estudiante
        const newStudent = {
            id: Date.now(),
            codigo: codigo,
            nombre: `${formData.get('nombre')} ${formData.get('apellido')}`,
            grado: formData.get('grado'),
            genero: formData.get('genero'),
            documento: formData.get('documento'),
            fechaNacimiento: formData.get('fechaNacimiento'),
            direccion: formData.get('direccion'),
            telefono: formData.get('telefono'),
            email: formData.get('email') || '',
            foto: photoFile ? URL.createObjectURL(photoFile) : getDefaultPhoto(formData.get('genero')),
            fechaRegistro: new Date().toISOString()
        };
        
        // Guardar en localStorage
        const students = JSON.parse(localStorage.getItem('students') || '[]');
        students.push(newStudent);
        localStorage.setItem('students', JSON.stringify(students));
        
        // Enviar al backend
        sendToBackend(newStudent);
        
        hideLoadingState();
        
        showToast(
            '🎉 ¡Estudiante registrado exitosamente!',
            'success'
        );
        
        // Mostrar código generado
        setTimeout(() => {
            showToast(
                `📋 Código asignado: ${codigo}`,
                'info'
            );
        }, 1000);
        
        // Limpiar formulario
        resetForm();
        
        // Redirigir después de un momento
        setTimeout(() => {
            window.location.href = 'students.html';
        }, 3000);
        
    }, 2000);
}

// Enviar datos al backend
function sendToBackend(studentData) {
    fetch('backend/procesar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'register_student',
            student: studentData
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta del servidor:', data);
    })
    .catch(error => {
        console.error('Error al enviar al servidor:', error);
    });
}

// Generar código único
function generateUniqueCode() {
    const students = JSON.parse(localStorage.getItem('students') || '[]');
    let codigo;
    
    do {
        const timestamp = Date.now().toString().slice(-4);
        const random = Math.floor(Math.random() * 900) + 100;
        codigo = `${random}${timestamp.slice(-2)}`;
    } while (students.some(s => s.codigo === codigo));
    
    return codigo;
}

// Obtener foto por defecto
function getDefaultPhoto(genero) {
    return genero === 'M' 
        ? "https://images.pexels.com/photos/1043471/pexels-photo-1043471.jpeg?auto=compress&cs=tinysrgb&w=50&h=50&fit=crop"
        : "https://images.pexels.com/photos/1239291/pexels-photo-1239291.jpeg?auto=compress&cs=tinysrgb&w=50&h=50&fit=crop";
}

// Mostrar estado de carga
function showLoadingState() {
    const button = document.querySelector('button[type="submit"]');
    button.disabled = true;
    button.classList.add('loading');
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
}

// Ocultar estado de carga
function hideLoadingState() {
    const button = document.querySelector('button[type="submit"]');
    button.disabled = false;
    button.classList.remove('loading');
    button.innerHTML = '<i class="fas fa-save"></i> Registrar Estudiante';
}

// Resetear formulario
function resetForm() {
    const form = document.getElementById('registerForm');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach((input, index) => {
        setTimeout(() => {
            if (input.type !== 'radio' && input.type !== 'file') {
                input.style.transform = 'translateX(-20px)';
                input.style.opacity = '0.5';
                
                setTimeout(() => {
                    input.value = '';
                    input.style.transform = 'translateX(0)';
                    input.style.opacity = '1';
                    
                    const formGroup = input.closest('.form-group');
                    formGroup.classList.remove('has-value', 'success', 'focused');
                }, 200);
            } else if (input.type === 'radio') {
                input.checked = false;
            }
        }, index * 50);
    });
    
    // Resetear área de foto
    setTimeout(() => {
        const uploadArea = document.querySelector('.upload-area');
        if (uploadArea) {
            uploadArea.innerHTML = `
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Haz clic para subir una foto</p>
                <span>JPG, PNG - Máximo 2MB</span>
            `;
        }
        photoFile = null;
    }, 1000);
}

// Función para mostrar notificaciones
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };

    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span style="font-size: 1.25rem;">${icons[type]}</span>
            <span>${message}</span>
        </div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 5000);
}

// Agregar estilos CSS para animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    .form-group.focused label {
        color: #667eea;
        transform: translateY(-2px);
    }
    
    .form-group.has-value label {
        font-weight: 700;
    }
    
    .form-group.success input,
    .form-group.success select {
        border-color: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    }
    
    .error-message {
        opacity: 0;
        transform: translateY(-10px);
        transition: all 0.3s ease;
    }
    
    .error-message.show {
        opacity: 1;
        transform: translateY(0);
    }
`;
document.head.appendChild(style);