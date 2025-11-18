// Variables globales
let attendanceChart = null;
let gradeChart = null;
let students = [];
let attendanceRecords = [];

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    loadData();
    updateStats();
    initializeCharts();
    updateReports();
});

// Cargar datos
function loadData() {
    const savedStudents = localStorage.getItem('students');
    const savedAttendance = localStorage.getItem('attendanceRecords');

    if (savedStudents) {
        students = JSON.parse(savedStudents);
    }

    if (savedAttendance) {
        attendanceRecords = JSON.parse(savedAttendance);
    }
}

// Actualizar estadísticas
function updateStats() {
    const today = new Date().toISOString().split('T')[0];
    const todayAttendance = attendanceRecords.filter(r => r.fecha === today).length;
    const totalStudents = students.length;
    const attendanceRate = totalStudents > 0 ? Math.round((todayAttendance / totalStudents) * 100) : 0;
    const absentToday = totalStudents - todayAttendance;

    // Actualizar elementos del DOM
    updateStatElement('totalStudentsStat', totalStudents);
    updateStatElement('presentTodayStat', todayAttendance);
    updateStatElement('attendanceRateStat', attendanceRate + '%');
    updateStatElement('absentTodayStat', absentToday);
}

// Actualizar elemento de estadística
function updateStatElement(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        animateCounter(element, 0, parseInt(value) || 0, value.toString().includes('%') ? '%' : '');
    }
}

// Animar contador
function animateCounter(element, start, end, suffix = '') {
    const duration = 1000;
    const startTime = performance.now();
    
    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const current = Math.floor(start + (end - start) * easeOutQuart(progress));
        element.textContent = current + suffix;
        
        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        }
    }
    
    requestAnimationFrame(updateCounter);
}

function easeOutQuart(t) {
    return 1 - (--t) * t * t * t;
}

// Inicializar gráficos
function initializeCharts() {
    initAttendanceChart();
    initGradeChart();
}

// Inicializar gráfico de asistencia
function initAttendanceChart() {
    const ctx = document.getElementById('attendanceChart');
    if (!ctx) return;

    // Datos de ejemplo para los últimos 7 días
    const last7Days = getLast7Days();
    const attendanceData = last7Days.map(date => {
        return attendanceRecords.filter(r => r.fecha === date).length;
    });

    attendanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: last7Days.map(date => {
                const d = new Date(date);
                return d.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' });
            }),
            datasets: [{
                label: 'Asistencias',
                data: attendanceData,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#667eea',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        color: '#6b7280'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#6b7280'
                    }
                }
            },
            elements: {
                point: {
                    hoverBackgroundColor: '#667eea'
                }
            }
        }
    });
}

// Inicializar gráfico por grado
function initGradeChart() {
    const ctx = document.getElementById('gradeChart');
    if (!ctx) return;

    // Contar estudiantes por grado
    const gradeData = {};
    students.forEach(student => {
        gradeData[student.grado] = (gradeData[student.grado] || 0) + 1;
    });

    const labels = Object.keys(gradeData);
    const data = Object.values(gradeData);
    const colors = [
        '#667eea', '#10b981', '#f59e0b', '#ef4444',
        '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'
    ];

    gradeChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 0,
                hoverBorderWidth: 2,
                hoverBorderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        color: '#6b7280'
                    }
                }
            },
            cutout: '60%'
        }
    });
}

// Obtener últimos 7 días
function getLast7Days() {
    const days = [];
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        days.push(date.toISOString().split('T')[0]);
    }
    return days;
}

// Cambiar período del gráfico
function changeChartPeriod(period) {
    // Actualizar botones activos
    document.querySelectorAll('.chart-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');

    // Actualizar datos del gráfico según el período
    let days, attendanceData;
    
    if (period === 'week') {
        days = getLast7Days();
    } else if (period === 'month') {
        days = getLast30Days();
    }

    attendanceData = days.map(date => {
        return attendanceRecords.filter(r => r.fecha === date).length;
    });

    // Actualizar gráfico
    if (attendanceChart) {
        attendanceChart.data.labels = days.map(date => {
            const d = new Date(date);
            return period === 'week' 
                ? d.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' })
                : d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
        });
        attendanceChart.data.datasets[0].data = attendanceData;
        attendanceChart.update();
    }
}

// Obtener últimos 30 días
function getLast30Days() {
    const days = [];
    for (let i = 29; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        days.push(date.toISOString().split('T')[0]);
    }
    return days;
}

// Actualizar reportes
function updateReports() {
    updateTopStudents();
    updateTopGrades();
}

// Actualizar estudiantes con mayor asistencia
function updateTopStudents() {
    // Calcular asistencia por estudiante
    const studentAttendance = {};
    
    students.forEach(student => {
        const attendanceCount = attendanceRecords.filter(r => r.studentCode === student.codigo).length;
        const totalDays = 30; // Últimos 30 días
        const rate = Math.round((attendanceCount / totalDays) * 100);
        
        studentAttendance[student.codigo] = {
            student: student,
            rate: rate
        };
    });

    // Ordenar por tasa de asistencia
    const topStudents = Object.values(studentAttendance)
        .sort((a, b) => b.rate - a.rate)
        .slice(0, 3);

    // Actualizar DOM
    const reportList = document.querySelector('.report-list');
    if (reportList) {
        reportList.innerHTML = topStudents.map(item => `
            <div class="report-item">
                <div class="student-info">
                    <img src="${item.student.foto}" alt="${item.student.nombre}">
                    <div>
                        <h4>${item.student.nombre}</h4>
                        <span>${item.student.grado}</span>
                    </div>
                </div>
                <div class="attendance-rate">${item.rate}%</div>
            </div>
        `).join('');
    }
}

// Actualizar grados con mejor asistencia
function updateTopGrades() {
    // Calcular asistencia por grado
    const gradeAttendance = {};
    
    students.forEach(student => {
        if (!gradeAttendance[student.grado]) {
            gradeAttendance[student.grado] = {
                totalStudents: 0,
                totalAttendance: 0
            };
        }
        
        gradeAttendance[student.grado].totalStudents++;
        gradeAttendance[student.grado].totalAttendance += 
            attendanceRecords.filter(r => r.studentCode === student.codigo).length;
    });

    // Calcular tasas y ordenar
    const gradeRates = Object.entries(gradeAttendance).map(([grade, data]) => {
        const rate = Math.round((data.totalAttendance / (data.totalStudents * 30)) * 100);
        return {
            grade: grade,
            students: data.totalStudents,
            rate: rate
        };
    }).sort((a, b) => b.rate - a.rate).slice(0, 3);

    // Actualizar DOM
    const gradeStats = document.querySelector('.grade-stats');
    if (gradeStats) {
        gradeStats.innerHTML = gradeRates.map(item => `
            <div class="grade-stat">
                <div class="grade-info">
                    <h4>${item.grade}</h4>
                    <span>${item.students} estudiantes</span>
                </div>
                <div class="grade-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${item.rate}%"></div>
                    </div>
                    <span>${item.rate}%</span>
                </div>
            </div>
        `).join('');
    }
}

// Exportar reporte en Excel
function exportReport() {
    const today = new Date().toISOString().split('T')[0];

    // Construir datos
    const reportData = [
        ["Fecha", today],
        ["Total Estudiantes", students.length],
        ["Asistencia Hoy", attendanceRecords.filter(r => r.fecha === today).length],
        ["Tasa de Asistencia (%)", students.length > 0 ? Math.round((attendanceRecords.filter(r => r.fecha === today).length / students.length) * 100) : 0],
        [],
        ["Código", "Nombre", "Grado", "Asistencias (últimos 30 días)"]
    ];

    students.forEach(student => {
        const asistencias = attendanceRecords.filter(r => r.studentCode === student.codigo).length;
        reportData.push([
            student.codigo,
            student.nombre,
            student.grado,
            asistencias
        ]);
    });

    // Crear Excel
    const ws = XLSX.utils.aoa_to_sheet(reportData);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Reporte");
    XLSX.writeFile(wb, `reporte_${today}.xlsx`);

    showToast('📊 Reporte exportado en Excel correctamente', 'success');
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

