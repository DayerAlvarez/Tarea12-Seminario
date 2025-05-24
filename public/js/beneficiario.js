/**
 * Funciones para el módulo de Beneficiarios
 */

// Abrir modal de registro de beneficiario
function abrirModal() {
    document.getElementById('modalBeneficiario').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Cerrar modal de registro de beneficiario
function cerrarModal() {
    document.getElementById('modalBeneficiario').style.display = 'none';
    document.body.style.overflow = 'auto';
    // Limpiar formulario
    document.querySelector('#modalBeneficiario form').reset();
}

// Inicialización del módulo
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar modal al hacer clic fuera de él
    window.onclick = function(event) {
        const modal = document.getElementById('modalBeneficiario');
        if (event.target == modal) {
            cerrarModal();
        }
    };

    // Validar solo números en DNI y teléfono
    const dniInput = document.getElementById('dni');
    if (dniInput) {
        dniInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 8);
        });
    }

    const telefonoInput = document.getElementById('telefono');
    if (telefonoInput) {
        telefonoInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 9);
        });
    }
});

// Validar formulario antes de enviar
function validarFormulario() {
    const dni = document.getElementById('dni').value;
    const telefono = document.getElementById('telefono').value;
    
    if (dni.length !== 8) {
        alert('El DNI debe tener exactamente 8 dígitos');
        return false;
    }
    
    if (telefono.length !== 9) {
        alert('El teléfono debe tener exactamente 9 dígitos');
        return false;
    }
    
    return true;
}