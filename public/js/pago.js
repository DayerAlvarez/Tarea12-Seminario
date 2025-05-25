/**
 * Funciones para el módulo de Pagos
 */

let contratoSeleccionado = null;
let cuotaSeleccionada = null;

// Abrir modal de registro de pago
function abrirModalPago() {
    document.getElementById('modalPago').style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Mostrar paso 1 y ocultar paso 2
    document.getElementById('paso1').style.display = 'block';
    document.getElementById('paso2').style.display = 'none';
    
    // Limpiar búsqueda anterior
    limpiarFormularioPago();
}

// Cerrar modal de registro de pago
function cerrarModalPago() {
    document.getElementById('modalPago').style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Limpiar formulario
    limpiarFormularioPago();
}

// Limpiar formulario de registro
function limpiarFormularioPago() {
    // Limpiar campo de búsqueda
    document.getElementById('dni_buscar').value = '';
    
    // Ocultar información del contrato
    document.getElementById('contratoInfo').style.display = 'none';
    document.getElementById('cuotasPendientes').innerHTML = '';
    
    // Deshabilitar botón de continuar
    document.getElementById('btnSiguientePaso').disabled = true;
    
    // Limpiar variables globales
    contratoSeleccionado = null;
    cuotaSeleccionada = null;
}

// Buscar contrato por DNI del beneficiario
function buscarContrato() {
    const dni = document.getElementById('dni_buscar').value.trim();
    const btnBuscar = document.getElementById('btnBuscarContrato');
    
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
    fetch(`?seccion=pagos&action=buscar_contrato&dni=${dni}`)
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
            mostrarContrato(data.datos);
        } else {
            alert(data.mensaje);
            ocultarContrato();
        }
    })
    .catch(error => {
        btnBuscar.disabled = false;
        btnBuscar.innerHTML = '🔍 Buscar';
        alert('Error al buscar contrato: ' + error.message);
        console.error('Error:', error);
        ocultarContrato();
    });
}

// Mostrar datos del contrato encontrado
function mostrarContrato(datos) {
    contratoSeleccionado = datos.contrato;
    
    // Mostrar el contenedor de información
    document.getElementById('contratoInfo').style.display = 'block';
    
    // Mostrar información del contrato
    document.getElementById('infoBeneficiario').textContent = datos.contrato.beneficiario_nombre;
    document.getElementById('infoDni').textContent = datos.contrato.beneficiario_dni;
    document.getElementById('infoContrato').textContent = datos.contrato.idcontrato;
    document.getElementById('infoMonto').textContent = `S/. ${parseFloat(datos.contrato.monto).toFixed(2)}`;
    
    // Mostrar cuotas pendientes
    const cuotasContainer = document.getElementById('cuotasPendientes');
    cuotasContainer.innerHTML = '';
    
    if (datos.cuotas && datos.cuotas.length > 0) {
        datos.cuotas.forEach(cuota => {
            // Calcular fecha de vencimiento - CORREGIDO: Usar Date en lugar de DateTime
            const fechaInicio = new Date(datos.contrato.fechainicio);
            fechaInicio.setMonth(fechaInicio.getMonth() + 1); // Primera cuota el mes siguiente
            const numCuota = parseInt(cuota.numcuota);
            const diaPago = parseInt(datos.contrato.diapago);
            
            const fechaVencimiento = new Date(fechaInicio);
            fechaVencimiento.setMonth(fechaVencimiento.getMonth() + (numCuota - 1));
            
            // Ajustar al día de pago
            const ultimoDiaMes = new Date(fechaVencimiento.getFullYear(), fechaVencimiento.getMonth() + 1, 0).getDate();
            fechaVencimiento.setDate(Math.min(diaPago, ultimoDiaMes));
            
            const fechaVencimientoFormateada = fechaVencimiento.toLocaleDateString('es-ES');
            
            // Verificar si la cuota está vencida
            const hoy = new Date();
            const estaVencida = fechaVencimiento < hoy;
            
            // Calcular penalidad si está vencida
            const penalidad = estaVencida ? parseFloat(cuota.monto) * 0.10 : 0;
            
            const cuotaItem = document.createElement('div');
            cuotaItem.className = 'cuota-item';
            cuotaItem.dataset.idpago = cuota.idpago;
            cuotaItem.dataset.numcuota = cuota.numcuota;
            cuotaItem.dataset.monto = cuota.monto;
            cuotaItem.dataset.penalidad = penalidad;
            cuotaItem.dataset.fechavencimiento = fechaVencimientoFormateada;
            cuotaItem.dataset.estavencida = estaVencida;
            
            cuotaItem.innerHTML = `
                <div class="cuota-grid">
                    <div class="cuota-info">
                        <span class="cuota-label">Cuota</span>
                        <span class="cuota-value">${cuota.numcuota} de ${datos.contrato.numcuotas}</span>
                    </div>
                    <div class="cuota-info">
                        <span class="cuota-label">Vencimiento</span>
                        <span class="cuota-value ${estaVencida ? 'cuota-vencimiento' : ''}">${fechaVencimientoFormateada}</span>
                    </div>
                    <div class="cuota-info">
                        <span class="cuota-label">Monto</span>
                        <span class="cuota-value">S/. ${parseFloat(cuota.monto).toFixed(2)}</span>
                    </div>
                </div>
            `;
            
            cuotaItem.addEventListener('click', function() {
                seleccionarCuota(this);
            });
            
            cuotasContainer.appendChild(cuotaItem);
        });
    } else {
        cuotasContainer.innerHTML = '<div class="no-cuotas">No hay cuotas pendientes para este contrato</div>';
    }
}

// Ocultar datos del contrato
function ocultarContrato() {
    document.getElementById('contratoInfo').style.display = 'none';
    document.getElementById('cuotasPendientes').innerHTML = '';
    document.getElementById('btnSiguientePaso').disabled = true;
    contratoSeleccionado = null;
    cuotaSeleccionada = null;
}

// Seleccionar una cuota para pagar
function seleccionarCuota(elemento) {
    // Quitar selección anterior
    const cuotasItems = document.querySelectorAll('.cuota-item');
    cuotasItems.forEach(item => {
        item.classList.remove('selected');
    });
    
    // Aplicar selección
    elemento.classList.add('selected');
    
    // Guardar datos de la cuota
    cuotaSeleccionada = {
        idpago: elemento.dataset.idpago,
        numcuota: elemento.dataset.numcuota,
        monto: parseFloat(elemento.dataset.monto),
        penalidad: parseFloat(elemento.dataset.penalidad),
        fechaVencimiento: elemento.dataset.fechavencimiento,
        estaVencida: elemento.dataset.estavencida === 'true'
    };
    
    // Habilitar botón de continuar
    document.getElementById('btnSiguientePaso').disabled = false;
}

// Mostrar paso 2 del formulario
function mostrarPaso2() {
    if (!cuotaSeleccionada) {
        alert('Debe seleccionar una cuota para continuar');
        return;
    }
    
    // Ocultar paso 1 y mostrar paso 2
    document.getElementById('paso1').style.display = 'none';
    document.getElementById('paso2').style.display = 'block';
    
    // Configurar formulario de pago
    document.getElementById('idpago').value = cuotaSeleccionada.idpago;
    
    // Mostrar fecha actual
    const fechaActual = new Date();
    // Formato de fecha y hora más legible y consistente con zona horaria local
    document.getElementById('fecha_actual').value = fechaActual.toLocaleDateString('es-ES') + ' ' + 
                                                    fechaActual.toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit'});
    
    // Mostrar datos de la cuota
    document.getElementById('cuota_detalle').value = `Cuota ${cuotaSeleccionada.numcuota} de ${contratoSeleccionado.numcuotas}`;
    document.getElementById('monto_cuota').value = `S/. ${cuotaSeleccionada.monto.toFixed(2)}`;
    
    // Mostrar penalidad si corresponde
    const penalidad = cuotaSeleccionada.penalidad;
    document.getElementById('penalidad').value = penalidad > 0 ? `S/. ${penalidad.toFixed(2)}` : 'S/. 0.00';
    
    // Calcular total
    const total = cuotaSeleccionada.monto + penalidad;
    document.getElementById('total_pagar').value = `S/. ${total.toFixed(2)}`;
}

// Volver al paso 1
function volverPaso1() {
    document.getElementById('paso1').style.display = 'block';
    document.getElementById('paso2').style.display = 'none';
}

// Validar formulario antes de enviar
function validarFormularioPago() {
    const medio = document.getElementById('medio').value;
    
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
                buscarContrato();
            }
        });
    }
});