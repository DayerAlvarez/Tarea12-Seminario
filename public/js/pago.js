/**
 * Funciones para el módulo de Pagos
 */

// Datos de pagos para el modal (se inicializará desde la vista)
let pagosData = [];

// Abrir modal para registro general de pago
function abrirModalPago() {
    alert('Para registrar un pago, haga clic en el botón "Registrar Pago" junto al pago pendiente.');
}

// Cerrar modal de registro de pago
function cerrarModalPago() {
    document.getElementById('modalPago').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('formPago').reset();
}

// Abrir modal para un pago específico
function registrarPago(idPago) {
    // Buscar el pago en los datos
    const pago = pagosData.find(p => p.idpago == idPago);
    if (!pago) {
        alert('Error: No se encontraron datos del pago.');
        return;
    }
    
    // Llenar el formulario
    document.getElementById('idcontrato').value = pago.idcontrato;
    document.getElementById('numcuota').value = pago.numcuota;
    document.getElementById('contrato_info').value = `Contrato #${pago.idcontrato}`;
    document.getElementById('beneficiario_info').value = pago.beneficiario_nombre;
    document.getElementById('cuota_info').value = `Cuota ${pago.numcuota} de ${pago.total_cuotas}`;
    document.getElementById('monto').value = pago.monto;
    
    // Mostrar el modal
    document.getElementById('modalPago').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Confirmar anulación de pago
function anularPago(idPago) {
    if (confirm('¿Está seguro de anular este pago? Esta acción no se puede deshacer.')) {
        window.location.href = `?seccion=pagos&action=anular&id=${idPago}`;
    }
}

// Validar formulario de pago
function validarFormularioPago() {
    const monto = parseFloat(document.getElementById('monto').value);
    const medio = document.getElementById('medio').value;
    
    if (!monto || monto <= 0) {
        alert('El monto debe ser mayor a 0');
        return false;
    }
    
    if (!medio) {
        alert('Debe seleccionar un medio de pago');
        return false;
    }
    
    return confirm('¿Está seguro de registrar este pago?');
}

// Inicialización del módulo
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar modal al hacer clic fuera de él
    window.onclick = function(event) {
        const modal = document.getElementById('modalPago');
        if (event.target == modal) {
            cerrarModalPago();
        }
    };

    // Validar solo números en campos de monto y penalidad
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

    const penalidadInput = document.getElementById('penalidad');
    if (penalidadInput) {
        penalidadInput.addEventListener('input', function(e) {
            let value = this.value;
            value = value.replace(/[^\d.]/g, '');
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            this.value = value;
        });
    }

    // Agregar validación al formulario
    const formPago = document.getElementById('formPago');
    if (formPago) {
        formPago.addEventListener('submit', function(e) {
            if (!validarFormularioPago()) {
                e.preventDefault();
            }
        });
    }
});