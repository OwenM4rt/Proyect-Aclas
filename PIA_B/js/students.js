// Variables globales
let students = [];
let filteredStudents = [];
let currentStudent = null;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    loadStudents();
    renderStudents();
    updateStudentsCount();
    setupEventListeners();
});

// Cargar estudiantes
function loadStudents() {
    const savedStudents = localStorage.getItem('students');
    
    if (savedStudents) {
        students = JSON.parse(savedStudents);
    } else {
        // Datos por defecto
        students = [
            {
                id: 1,
                codigo: "123",
                nombre: "Juan Pérez",
                grado: "10-1",
                genero: "M",
                documento: "12345678",
                telefono: "555-0123",
                direccion: "Calle 123 #45-67",
                email: "juan.perez@email.com",
                fechaNacimiento: "2005-03-15",
                foto: "https://images.pexels.com/photos/1043471/pexels-photo-1043471.jpeg?auto=compress&cs=tinysrgb&w=50&h=50&fit=crop",
                fechaRegistro: new Date().toISOString()
            },
            {
                id: 2,
                codigo: "124",
                nombre: "María López",
                grado: "9-2",
                genero: "F",
                documento: "87654321",
                telefono: "555-0124",
                direccion: "Carrera 45 #12-34",
                email: "maria.lopez@email.com",
                fechaNacimiento: "2006-07-22",
                foto: "https://images.pexels.com/photos/1239291/pexels-photo-1239291.jpeg?auto=compress&cs=tinysrgb&w=50&h=50&fit=crop",
                fechaRegistro: new Date().toISOString()
            },
            {
                id: 3,
                codigo: "125",
                nombre: "Carlos Ramírez",
                grado: "11-1",
                genero: "M",
                documento: "11223344",
                telefono: "555-0125",
                direccion: "Avenida 67 #89-01",
                email: "carlos.ramirez@email.com",
                fechaNacimiento: "2004-11-08",
                foto: "https://images.pexels.com/photos/1043471/pexels-photo-1043471.jpeg?auto=compress&cs=tinysrgb&w=50&h=50&fit=crop",
                fechaRegistro: new Date().toISOString()
            }
        ];
        
        localStorage.setItem('students', JSON.stringify(students));
    }
    
    filteredStudents = [...students];
}

// Configurar event listeners
function setupEventListeners() {
    // Cerrar modal al hacer clic fuera
    const modal = document.getElementById('studentModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    
    // Cerrar modal con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

// Renderizar estudiantes
function renderStudents() {
    const grid = document.getElementById('studentsGrid');
    const emptyState = document.getElementById('emptyState');
    
    if (!grid || !emptyState) return;
    
    if (filteredStudents.length === 0) {
        grid.style.display = 'none';
        emptyState.style.display = 'block';
    } else {
        emptyState.style.display = 'none';
        grid.style.display = 'grid';
        
        grid.innerHTML = '';
        
        filteredStudents.forEach((student, index) => {
            const card = createStudentCard(student);
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            grid.appendChild(card);
            
            // Animación de entrada
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }
}

// Crear tarjeta de estudiante
function createStudentCard(student) {
    const card = document.createElement('div');
    card.className = 'student-card';
    card.onclick = () => openModal(student);
    
    const fechaRegistro = new Date(student.fechaRegistro).toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
    
    card.innerHTML = `
        <div class="student-card-header">
            <img src="${student.foto}" alt="${student.nombre}" class="student-avatar">
            <div class="student-basic-info">
                <h3>${student.nombre}</h3>
                <span class="student-code">${student.codigo}</span>
            </div>
        </div>
        <div class="student-details">
            <div class="detail-item">
                <span class="detail-label">Grado</span>
                <span class="detail-value">
                    <span class="grade-badge">${student.grado}</span>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Género</span>
                <span class="detail-value">
                    <span class="gender-badge">${student.genero === 'M' ? 'Masculino' : 'Femenino'}</span>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Documento</span>
                <span class="detail-value">${student.documento}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Registro</span>
                <span class="detail-value">${fechaRegistro}</span>
            </div>
        </div>
        <div class="student-actions">
            <button class="action-btn edit" onclick="event.stopPropagation(); editStudentInline(${student.id})" title="Editar">
                <i class="fas fa-edit"></i>
            </button>
            <button class="action-btn delete" onclick="event.stopPropagation(); deleteStudentConfirm(${student.id})" title="Eliminar">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    return card;
}

// Abrir modal
function openModal(student) {
    currentStudent = student;
    const modal = document.getElementById('studentModal');
    
    if (!modal) return;
    
    // Llenar datos del modal
    document.getElementById('modalPhoto').src = student.foto;
    document.getElementById('modalName').textContent = student.nombre;
    document.getElementById('modalCode').textContent = student.codigo;
    document.getElementById('modalGrade').textContent = student.grado;
    document.getElementById('modalGender').textContent = student.genero === 'M' ? 'Masculino' : 'Femenino';
    
    const fechaRegistro = new Date(student.fechaRegistro).toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
    document.getElementById('modalDate').textContent = fechaRegistro;
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Cerrar modal
function closeModal() {
    const modal = document.getElementById('studentModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
        currentStudent = null;
    }
}

// Editar estudiante
function editStudent() {
    if (currentStudent) {
        showToast(`Función de edición para ${currentStudent.nombre} en desarrollo`, 'info');
        closeModal();
    }
}

// Editar estudiante inline
function editStudentInline(studentId) {
    const student = students.find(s => s.id === studentId);
    if (student) {
        showToast(`Editando ${student.nombre}...`, 'info');
        // Aquí se implementaría la lógica de edición
    }
}

// Eliminar estudiante
function deleteStudent() {
    if (currentStudent && confirm(`¿Estás seguro de eliminar a ${currentStudent.nombre}?`)) {
        students = students.filter(s => s.id !== currentStudent.id);
        filteredStudents = filteredStudents.filter(s => s.id !== currentStudent.id);
        
        localStorage.setItem('students', JSON.stringify(students));
        
        renderStudents();
        updateStudentsCount();
        closeModal();
        
        showToast(`${currentStudent.nombre} eliminado correctamente`, 'success');
    }
}

// Confirmar eliminación
function deleteStudentConfirm(studentId) {
    const student = students.find(s => s.id === studentId);
    if (student && confirm(`¿Estás seguro de eliminar a ${student.nombre}?`)) {
        students = students.filter(s => s.id !== studentId);
        filteredStudents = filteredStudents.filter(s => s.id !== studentId);
        
        localStorage.setItem('students', JSON.stringify(students));
        
        renderStudents();
        updateStudentsCount();
        
        showToast(`${student.nombre} eliminado correctamente`, 'success');
    }
}

// Filtrar estudiantes
function filterStudents(searchTerm) {
    filteredStudents = students.filter(student => 
        student.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
        student.codigo.includes(searchTerm) ||
        student.documento.includes(searchTerm)
    );
    
    renderStudents();
    updateStudentsCount();
}

// Filtrar por grado
function filterByGrade(grade) {
    if (grade === '') {
        filteredStudents = [...students];
    } else {
        filteredStudents = students.filter(student => student.grado === grade);
    }
    
    renderStudents();
    updateStudentsCount();
}

// Filtrar por género
function filterByGender(gender) {
    if (gender === '') {
        filteredStudents = [...students];
    } else {
        filteredStudents = students.filter(student => student.genero === gender);
    }
    
    renderStudents();
    updateStudentsCount();
}

// Actualizar contador de estudiantes
function updateStudentsCount() {
    const totalElement = document.getElementById('totalStudentsCount');
    const countElement = document.getElementById('studentsCount');
    
    if (totalElement) {
        totalElement.textContent = `${students.length} Estudiantes`;
    }
    
    if (countElement) {
        countElement.textContent = `${filteredStudents.length} estudiantes`;
    }
}

// Exportar estudiantes
function exportStudents() {
    if (students.length === 0) {
        showToast('⚠️ No hay estudiantes para exportar', 'warning');
        return;
    }
    
    // Crear CSV
    const headers = ['Código', 'Nombre', 'Grado', 'Género', 'Documento', 'Teléfono', 'Email', 'Fecha Registro'];
    const csvContent = [
        headers.join(','),
        ...students.map(student => [
            student.codigo,
            student.nombre,
            student.grado,
            student.genero === 'M' ? 'Masculino' : 'Femenino',
            student.documento,
            student.telefono,
            student.email || '',
            new Date(student.fechaRegistro).toLocaleDateString('es-ES')
        ].join(','))
    ].join('\n');
    
    // Descargar archivo
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `estudiantes_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
    
    showToast('📄 Lista de estudiantes exportada correctamente', 'success');
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
    }, 4000);
}