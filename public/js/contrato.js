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
    
    // Validar que la fecha no sea anterior a hoy - CORREGIDO
    // Obtenemos solo la fecha sin hora
    const hoy = new Date();
    const fechaHoy = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
    
    const partesFechaSeleccionada = fechainicio.split('-');
    const fechaSeleccionada = new Date(
        parseInt(partesFechaSeleccionada[0]), 
        parseInt(partesFechaSeleccionada[1]) - 1, // Los meses en JS van de 0-11
        parseInt(partesFechaSeleccionada[2])
    );
    
    // Comparación solo de fechas sin tiempo
    if (fechaSeleccionada < fechaHoy) {
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

// Abrir modal de cronograma
function verCronograma(idContrato) {
    // Mostrar modal de cronograma
    document.getElementById('modalCronograma').style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Mostrar loader y ocultar contenido
    document.getElementById('cronogramaLoader').style.display = 'block';
    document.getElementById('cronogramaInfo').style.display = 'none';
    document.getElementById('cronogramaTabla').innerHTML = '';
    
    // Hacer petición para obtener cronograma
    fetch(`?seccion=contratos&action=cronograma&id=${idContrato}`)
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        document.getElementById('cronogramaLoader').style.display = 'none';
        
        if (data.exito) {
            mostrarCronograma(data.datos);
        } else {
            document.getElementById('cronogramaTabla').innerHTML = `
                <div class="no-data">
                    <p>Error: ${data.mensaje}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        document.getElementById('cronogramaLoader').style.display = 'none';
        document.getElementById('cronogramaTabla').innerHTML = `
            <div class="no-data">
                <p>Error al cargar cronograma: ${error.message}</p>
            </div>
        `;
        console.error('Error:', error);
    });
}

// Cerrar modal de cronograma
function cerrarModalCronograma() {
    document.getElementById('modalCronograma').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Mostrar cronograma
function mostrarCronograma(datos) {
    const contrato = datos.contrato;
    const cuotas = datos.cuotas;
    
    // Mostrar información del contrato
    document.getElementById('cronogramaInfo').style.display = 'block';
    document.getElementById('cronogramaTitulo').textContent = `Cronograma de Pagos - Contrato #${contrato.idcontrato}`;
    document.getElementById('cronogramaBeneficiario').textContent = contrato.beneficiario_nombre;
    document.getElementById('cronogramaDni').textContent = contrato.beneficiario_dni;
    document.getElementById('cronogramaMonto').textContent = `S/. ${parseFloat(contrato.monto).toFixed(2)}`;
    document.getElementById('cronogramaInteres').textContent = `${parseFloat(contrato.interes).toFixed(2)}%`;
    
    // Generar tabla de cuotas
    let tablaHTML = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>N° Cuota</th>
                    <th>Fecha Vencimiento</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Fecha Pago</th>
                    <th>Penalidad</th>
                    <th>Total Pagado</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    cuotas.forEach(cuota => {
        const fechaVencimiento = new Date(cuota.fecha_vencimiento);
        const fechaVencimientoFormatted = fechaVencimiento.toLocaleDateString('es-ES');
        
        const estaPagada = cuota.fechapago !== null;
        const estaVencida = !estaPagada && new Date() > fechaVencimiento;
        
        const estadoClass = estaPagada ? 'estado-pagado' : (estaVencida ? 'estado-vencido' : 'estado-pendiente');
        const estadoTexto = estaPagada ? 'Pagado' : (estaVencida ? 'Vencido' : 'Pendiente');
        
        let fechaPagoFormatted = '-';
        let penalidad = '-';
        let totalPagado = '-';
        
        if (estaPagada) {
            const fechaPago = new Date(cuota.fechapago);
            fechaPagoFormatted = fechaPago.toLocaleDateString('es-ES');
            penalidad = `S/. ${parseFloat(cuota.penalidad).toFixed(2)}`;
            totalPagado = `S/. ${(parseFloat(cuota.monto) + parseFloat(cuota.penalidad)).toFixed(2)}`;
        }
        
        tablaHTML += `
            <tr>
                <td>${cuota.numcuota}</td>
                <td>${fechaVencimientoFormatted}</td>
                <td class="currency">S/. ${parseFloat(cuota.monto).toFixed(2)}</td>
                <td><span class="badge ${estadoClass}">${estadoTexto}</span></td>
                <td>${fechaPagoFormatted}</td>
                <td>${penalidad}</td>
                <td>${totalPagado}</td>
            </tr>
        `;
    });
    
    tablaHTML += `
            </tbody>
        </table>
    `;
    
    document.getElementById('cronogramaTabla').innerHTML = tablaHTML;
}

// Inicialización del módulo
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar modal al hacer clic fuera de él
    window.onclick = function(event) {
        const modalContrato = document.getElementById('modalContrato');
        const modalCronograma = document.getElementById('modalCronograma');
        
        if (event.target == modalContrato) {
            cerrarModal();
        } else if (event.target == modalCronograma) {
            cerrarModalCronograma();
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