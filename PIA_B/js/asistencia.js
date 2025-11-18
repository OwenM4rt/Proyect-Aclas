// Establecer fecha actual
document.getElementById("currentDate").innerText = new Date().toLocaleDateString('es-ES', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
});

let unsavedChanges = false;

// Sistema de notificaciones toast
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const icon = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    }[type] || 'fa-info-circle';
    
    toast.innerHTML = `
        <i class="fas ${icon}"></i>
        <span>${message}</span>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('toast-removing');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Advertencia al salir con cambios sin guardar
window.addEventListener('beforeunload', function (e) {
    if (unsavedChanges) {
        e.preventDefault(); 
        e.returnValue = 'Tiene cambios sin guardar. ¿Está seguro que desea salir?'; 
        return e.returnValue;
    }
});

// Marcar que hay cambios pendientes
function markAsUnsaved() {
    unsavedChanges = true;
    const saveBtn = document.querySelector('.save-btn');
    if (saveBtn) {
        saveBtn.classList.add('btn-pulse');
    }
}

// Cargar registros de asistencia del día
async function loadTodayAttendance() {
    try {
        const response = await fetch("../php/students.php?action=get_attendance");
        if (!response.ok) {
            throw new Error("Error al obtener asistencia: " + response.status);
        }

        const asistencias = await response.json();
        const tbody = document.getElementById("cuerpoTabla");
        tbody.innerHTML = ''; 
        
        unsavedChanges = false;
        const saveBtn = document.querySelector('.save-btn');
        if (saveBtn) {
            saveBtn.classList.remove('btn-pulse');
        }

        if (!asistencias || asistencias.length === 0) {
            actualizarContador();
            return;
        }

        for (const registro of asistencias) {
            const row = document.createElement("tr");
            row.setAttribute('data-codigo', registro.codigo);
            row.innerHTML = `
                <td class="codigo-highlight">${registro.codigo}</td>
                <td class="nombre-cell">${registro.nombre}</td> 
                <td><span class="grado-badge">${registro.grado}</span></td>
                <td>${registro.hora}</td>
                <td><span class="status-badge status-present">${registro.estado}</span></td>
                <td>
                    <button class="btnEliminar" onclick="eliminarFila(this)" title="Eliminar registro">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        }

        actualizarContador();
        filterTable();
        
    } catch (error) {
        console.error("❌ Error al cargar asistencias iniciales:", error);
        showToast("Error al cargar registros guardados. Revisa la conexión.", 'error');
    }
}

// Eliminar fila de asistencia
function eliminarFila(button) {
    const row = button.closest('tr');
    const nombre = row.cells[1].innerText;
    
    if (confirm(`¿Está seguro de eliminar el registro de ${nombre}?`)) {
        row.classList.add('row-removing');
        setTimeout(() => {
            row.remove();
            actualizarContador();
            markAsUnsaved();
            showToast(`Registro de ${nombre} eliminado`, 'info');
        }, 300);
    }
}

// Registrar nueva asistencia
async function registrarAsistencia() {
    const codigo = document.getElementById("codigoInput").value.trim();
    
    if (!codigo) {
        showToast("Por favor ingresa un código de estudiante", 'warning');
        document.getElementById("codigoInput").focus();
        return;
    }

    try {
        // Verificar si ya está registrado
        const rows = document.querySelectorAll("#cuerpoTabla tr");
        let yaRegistrado = false;
        
        rows.forEach(row => {
            const codigoCelda = row.cells[0].innerText.trim();
            if (codigoCelda === codigo) {
                yaRegistrado = true;
            }
        });

        if (yaRegistrado) {
            showToast("Este estudiante ya registró asistencia hoy", 'error');
            document.getElementById("codigoInput").value = "";
            document.getElementById("codigoInput").focus();
            return;
        }

        // Buscar estudiante en la base de datos
        const response = await fetch(`../php/students.php?action=find&codigo=${encodeURIComponent(codigo)}`);
        
        if (!response.ok) {
            throw new Error("Error en la petición: " + response.status);
        }

        const estudiante = await response.json();

        if (!estudiante || !estudiante.nombre) {
            showToast("Estudiante no encontrado en la base de datos", 'error');
            document.getElementById("codigoInput").value = "";
            document.getElementById("codigoInput").focus();
            return;
        }

        // Actualizar vista previa del estudiante
        document.getElementById("studentName").innerText = estudiante.nombre;
        document.getElementById("studentGrade").innerText = estudiante.grado;
        document.getElementById("studentCode").innerText = estudiante.codigo;

        // Agregar a la tabla
        const tbody = document.getElementById("cuerpoTabla");
        const now = new Date();
        const horaRegistro = now.toLocaleTimeString('es-ES', { 
            hour: '2-digit', 
            minute: '2-digit',
            second: '2-digit'
        });

        const row = document.createElement("tr");
        row.setAttribute('data-codigo', estudiante.codigo);
        row.innerHTML = `
            <td class="codigo-highlight">${estudiante.codigo}</td>
            <td class="nombre-cell">${estudiante.nombre}</td>
            <td><span class="grado-badge">${estudiante.grado}</span></td>
            <td>${horaRegistro}</td>
            <td><span class="status-badge status-present">Presente</span></td>
            <td>
                <button class="btnEliminar" onclick="eliminarFila(this)" title="Eliminar registro">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        // Animación de entrada
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';
        tbody.appendChild(row);
        
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        }, 10);

        markAsUnsaved();
        actualizarContador();
        filterTable(); 
        showToast(`Asistencia registrada: ${estudiante.nombre}`, 'success');

        // Limpiar input
        document.getElementById("codigoInput").value = "";
        document.getElementById("codigoInput").focus();

    } catch (error) {
        console.error("❌ Error en registrarAsistencia:", error);
        showToast("Error al buscar estudiante. Revisa la consola.", 'error');
    }
}

// Actualizar contador de asistentes
function actualizarContador() {
    const tbody = document.getElementById("cuerpoTabla");
    const allRows = tbody.rows.length;
    const visibleRows = Array.from(tbody.rows).filter(row => row.style.display !== 'none').length;
    
    document.getElementById("contadorAsistentes").innerText = `${allRows} Registrados`;
    document.getElementById("totalPresentes").innerText = allRows;
    
    const emptyState = document.getElementById("emptyState");
    const tableContainer = document.querySelector(".table-container");
    
    if (allRows === 0) {
        emptyState.style.display = "flex";
        tableContainer.style.display = "none";
    } else {
        emptyState.style.display = "none";
        tableContainer.style.display = "block";
    }
}

// Filtrar tabla de asistencia
function filterTable() {
    const searchText = document.getElementById("searchTableInput").value.trim().toLowerCase();
    const searchCode = document.getElementById("studentCodeFilter").value.trim().toLowerCase();
    const filterGrade = document.getElementById("gradeFilter").value.trim();
    const rows = document.querySelectorAll("#cuerpoTabla tr");
    
    let visibleCount = 0;
    
    rows.forEach(row => {
        const codigo = row.cells[0].innerText.toLowerCase();
        const nombre = row.cells[1].innerText.toLowerCase();
        const grado = row.cells[2].innerText.trim();

        const textMatch = (nombre.includes(searchText) || codigo.includes(searchText));
        const codeMatch = (searchCode === "" || codigo.includes(searchCode));
        const gradeMatch = (filterGrade === "" || grado === filterGrade);

        if (textMatch && codeMatch && gradeMatch) {
            row.style.display = "";
            visibleCount++;
        } else {
            row.style.display = "none";
        }
    });
    
    // Mostrar mensaje si no hay resultados
    const tbody = document.getElementById("cuerpoTabla");
    const emptyState = document.getElementById("emptyState");
    
    if (tbody.rows.length > 0 && visibleCount === 0) {
        emptyState.style.display = "flex";
        emptyState.querySelector("h3").innerText = "No se encontraron resultados";
        emptyState.querySelector("p").innerText = "Intenta con otros términos de búsqueda";
    } else if (tbody.rows.length === 0) {
        emptyState.querySelector("h3").innerText = "No hay registros de asistencia";
        emptyState.querySelector("p").innerText = "Los estudiantes que registren su asistencia aparecerán aquí";
    }
}

// Exportar asistencia a Excel
function exportAttendance() {
    const tbody = document.getElementById("cuerpoTabla");
    
    if (tbody.rows.length === 0) {
        showToast("No hay registros para exportar", 'warning');
        return;
    }
    
    const table = document.getElementById('tablaAsistencia');
    const ws = XLSX.utils.table_to_sheet(table);
    
    // Ajustar ancho de columnas
    const wscols = [
        {wch: 12},  // Código
        {wch: 30},  // Nombre
        {wch: 12},  // Grado
        {wch: 15},  // Hora
        {wch: 12},  // Estado
    ];
    ws['!cols'] = wscols;
    
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Asistencia");

    const today = new Date().toLocaleDateString('es-ES').replace(/\//g, '-');
    const filename = `Asistencia_${today}.xlsx`;
    
    XLSX.writeFile(wb, filename);

    showToast(`Reporte exportado: ${filename}`, 'success');
}

// Guardar cambios en la base de datos
async function guardarCambios() {
    if (!unsavedChanges) {
        showToast("No hay cambios nuevos para guardar", 'info');
        return;
    }

    const rows = document.querySelectorAll("#cuerpoTabla tr");
    
    if (rows.length === 0) {
        showToast("No hay asistencias para guardar", 'warning');
        return;
    }

    const asistenciaData = [];
    rows.forEach(row => {
        asistenciaData.push({
            codigo: row.cells[0].innerText.trim(),
            grado: row.cells[2].innerText.trim(),
            hora: row.cells[3].innerText.trim(),
            estado: row.cells[4].innerText.trim(),
        });
    });

    try {
        showToast("Guardando cambios...", 'info');
        
        const response = await fetch("../php/students.php?action=save_attendance", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(asistenciaData),
        });

        const result = await response.json();

        if (response.ok && result.success) {
            unsavedChanges = false;
            const saveBtn = document.querySelector('.save-btn');
            if (saveBtn) {
                saveBtn.classList.remove('btn-pulse');
            }
            showToast("Asistencia guardada correctamente en la base de datos", 'success');
        } else {
            throw new Error(result.message || "Error desconocido al guardar.");
        }

    } catch (error) {
        console.error("❌ Error al guardar asistencia:", error);
        showToast(`Error al guardar: ${error.message}`, 'error');
    }
}

// Inicializar eventos del teclado
document.addEventListener('DOMContentLoaded', function() {
    const codigoInput = document.getElementById('codigoInput');
    if (codigoInput) {
        codigoInput.focus();
    }
});
