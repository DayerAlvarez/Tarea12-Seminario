/**
 * Funciones para el módulo de Contratos
 */

let beneficiarioSeleccionado = null;
let tieneContratosActivos = false;

// Abrir modal de registro de contrato
function abrirModal() {
    document.getElementById('modalContrato').style.display = 'block';
    document.body.style.overflow = 'hidden';
    // Establecer fecha mínima como hoy
    document.getElementById('fechainicio').value = new Date().toISOString().split('T')[0];
    // Limpiar búsqueda anterior
    limpiarFormulario();
}

// Cerrar modal de registro de contrato
function cerrarModal() {
    document.getElementById('modalContrato').style.display = 'none';
    document.body.style.overflow = 'auto';
    // Limpiar formulario
    limpiarFormulario();
}

// Limpiar formulario de registro
function limpiarFormulario() {
    document.querySelector('#modalContrato form').reset();
    document.getElementById('nombre_beneficiario').value = '';
    document.getElementById('alertaContrato').classList.remove('show');
    document.getElementById('loanSummary').style.display = 'none';
    
    // Deshabilitar campos
    const campos = ['monto', 'interes', 'numcuotas', 'fechainicio', 'diapago'];
    campos.forEach(campo => {
        document.getElementById(campo).disabled = true;
    });
    
    document.getElementById('btnCrearContrato').disabled = true;
    beneficiarioSeleccionado = null;
    tieneContratosActivos = false;
    
    // Restablecer fecha mínima
    document.getElementById('fechainicio').value = new Date().toISOString().split('T')[0];
}

// Buscar beneficiario por DNI
function buscarBeneficiario() {
    const dni = document.getElementById('dni_buscar').value.trim();
    const btnBuscar = document.getElementById('btnBuscar');
    
    if (!dni) {
        alert('Por favor ingrese un DNI');
        return;
    }
    
    if (dni.length !== 8 || !/^\d{8}$/.test(dni)) {
        alert('El DNI debe tener exactamente 8 dígitos');
        return;
    }
    
    // Mostrar loading
    btnBuscar.disabled = true;
    btnBuscar.innerHTML = '<div class="loading"></div> Buscando...';
    
    // Hacer petición con fetch
    fetch(`?seccion=contratos&action=buscar_beneficiario&dni=${dni}`)
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        btnBuscar.disabled = false;
        btnBuscar.innerHTML = '🔍 Buscar';
        
        if (data.exito) {
            mostrarBeneficiario(data.datos, data.tieneContratosActivos);
        } else {
            alert(data.mensaje);
            ocultarBeneficiario();
        }
    })
    .catch(error => {
        btnBuscar.disabled = false;
        btnBuscar.innerHTML = '🔍 Buscar';
        alert('Error al buscar beneficiario: ' + error.message);
        console.error('Error:', error);
        ocultarBeneficiario();
    });
}

// Mostrar datos del beneficiario encontrado
function mostrarBeneficiario(beneficiario, tieneContratos) {
    beneficiarioSeleccionado = beneficiario;
    tieneContratosActivos = tieneContratos;
    
    // Mostrar nombre completo en el campo bloqueado
    document.getElementById('nombre_beneficiario').value = beneficiario.nombre_completo;
    
    // Establecer ID del beneficiario en el campo oculto
    document.getElementById('idbeneficiario').value = beneficiario.idbeneficiario;
    
    if (tieneContratos) {
        // Mostrar alerta de contratos activos
        document.getElementById('alertaContrato').classList.add('show');
        
        // Mantener campos deshabilitados
        const campos = ['monto', 'interes', 'numcuotas', 'fechainicio', 'diapago'];
        campos.forEach(campo => {
            document.getElementById(campo).disabled = true;
        });
        
        document.getElementById('btnCrearContrato').disabled = true;
    } else {
        // Ocultar alerta
        document.getElementById('alertaContrato').classList.remove('show');
        
        // Habilitar campos del formulario
        const campos = ['monto', 'interes', 'numcuotas', 'fechainicio', 'diapago'];
        campos.forEach(campo => {
            document.getElementById(campo).disabled = false;
        });
        
        document.getElementById('btnCrearContrato').disabled = false;
    }
}

// Ocultar datos del beneficiario
function ocultarBeneficiario() {
    document.getElementById('nombre_beneficiario').value = '';
    document.getElementById('alertaContrato').classList.remove('show');
    document.getElementById('loanSummary').style.display = 'none';
    
    // Deshabilitar campos
    const campos = ['monto', 'interes', 'numcuotas', 'fechainicio', 'diapago'];
    campos.forEach(campo => {
        document.getElementById(campo).disabled = true;
    });
    
    document.getElementById('btnCrearContrato').disabled = true;
    document.getElementById('idbeneficiario').value = '';
    beneficiarioSeleccionado = null;
    tieneContratosActivos = false;
}

// Calcular resumen del préstamo
function calcularResumen() {
    const monto = parseFloat(document.getElementById('monto').value) || 0;
    const numCuotas = parseInt(document.getElementById('numcuotas').value) || 0;
    const interes = parseFloat(document.getElementById('interes').value) || 0;
    
    if (monto > 0 && numCuotas > 0 && interes >= 0) {
        // Mostrar el resumen
        document.getElementById('loanSummary').style.display = 'block';
        
        // Calcular usando fórmula de amortización francesa
        const tasaMensual = interes / 100; // 5% = 0.05
        let cuotaMensual;
        
        if (tasaMensual === 0) {
            // Si no hay interés, solo dividir el capital
            cuotaMensual = monto / numCuotas;
        } else {
            // Fórmula: C = P × [i × (1+i)^n] / [(1+i)^n - 1]
            const factor = Math.pow(1 + tasaMensual, numCuotas);
            cuotaMensual = monto * (tasaMensual * factor) / (factor - 1);
        }
        
        const totalPagar = cuotaMensual * numCuotas;
        const totalIntereses = totalPagar - monto;
        
        // Actualizar valores en el resumen
        document.getElementById('cuotaMensual').textContent = 'S/. ' + cuotaMensual.toFixed(2);
        document.getElementById('totalPagar').textContent = 'S/. ' + totalPagar.toFixed(2);
        document.getElementById('totalIntereses').textContent = 'S/. ' + totalIntereses.toFixed(2);
    } else {
        document.getElementById('loanSummary').style.display = 'none';
    }
}

// Validar formulario antes de enviar
function validarFormulario() {
    if (!beneficiarioSeleccionado) {
        alert('Debe buscar y seleccionar un beneficiario válido');
        return false;
    }
    
    if (tieneContratosActivos) {
        alert('El beneficiario seleccionado ya tiene un contrato activo. No se puede crear un nuevo contrato.');
        return false;
    }
    
    const monto = parseFloat(document.getElementById('monto').value);
    const interes = parseFloat(document.getElementById('interes').value);
    const fechainicio = document.getElementById('fechainicio').value;
    const diapago = document.getElementById('diapago').value;
    const numcuotas = parseInt(document.getElementById('numcuotas').value);
    
    if (!monto || monto <= 0) {
        alert('El monto debe ser mayor a 0');
        return false;
    }
    
    if (isNaN(interes) || interes < 0 || interes > 100) {
        alert('El interés debe ser entre 0 y 100%');
        return false;
    }
    
    if (!fechainicio) {
        alert('Debe seleccionar una fecha de inicio');
        return false;
    }
    
    // Validar que la fecha no sea anterior a hoy
    const hoy = new Date();
    const fechaSeleccionada = new Date(fechainicio);
    if (fechaSeleccionada < hoy.setHours(0,0,0,0)) {
        alert('La fecha de inicio no puede ser anterior a hoy');
        return false;
    }
    
    if (!diapago) {
        alert('Debe seleccionar un día de pago');
        return false;
    }
    
    if (!numcuotas || numcuotas < 1 || numcuotas > 255) {
        alert('El número de cuotas debe ser entre 1 y 255');
        return false;
    }
    
    return confirm(`¿Está seguro de crear este contrato de préstamo para ${beneficiarioSeleccionado.nombre_completo}?`);
}

// Inicialización del módulo
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar modal al hacer clic fuera de él
    window.onclick = function(event) {
        const modal = document.getElementById('modalContrato');
        if (event.target == modal) {
            cerrarModal();
        }
    };

    // Formatear DNI mientras se escribe
    const dniBuscarInput = document.getElementById('dni_buscar');
    if (dniBuscarInput) {
        dniBuscarInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^\d]/g, '');
            if (this.value.length > 8) {
                this.value = this.value.slice(0, 8);
            }
        });

        // Permitir buscar con Enter
        dniBuscarInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarBeneficiario();
            }
        });
    }

    // Formatear monto mientras se escribe
    const montoInput = document.getElementById('monto');
    if (montoInput) {
        montoInput.addEventListener('input', function(e) {
            let value = this.value;
            value = value.replace(/[^\d.]/g, '');
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            this.value = value;
        });
    }

    // Formatear interés
    const interesInput = document.getElementById('interes');
    if (interesInput) {
        interesInput.addEventListener('input', function(e) {
            let value = this.value;
            value = value.replace(/[^\d.]/g, '');
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            this.value = value;
        });
    }

    // Formatear número de cuotas (solo números enteros)
    const numCuotasInput = document.getElementById('numcuotas');
    if (numCuotasInput) {
        numCuotasInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^\d]/g, '');
            if (parseInt(this.value) > 255) {
                this.value = '255';
            }
        });
    }
});