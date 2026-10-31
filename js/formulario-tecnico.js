// Finalizar exige diagnóstico y trabajo; guardar un avance permite dejarlos pendientes.
const formularioTrabajo = document.getElementById('formulario-trabajo');

if (formularioTrabajo) {
    const diagnostico = formularioTrabajo.querySelector('[name="diagnostico"]');
    const trabajo = formularioTrabajo.querySelector('[name="trabajo_realizado"]');

    formularioTrabajo.querySelectorAll('button[name="accion"]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const finalizar = boton.value === 'finalizar';

            [diagnostico, trabajo].forEach((campo) => {
                campo.required = finalizar;
                campo.setCustomValidity(
                    finalizar && campo.value.trim() === ''
                        ? 'Completa este campo para finalizar la orden.'
                        : ''
                );
            });
        });
    });

    // Quita el aviso de validación cuando el técnico empieza a corregir el campo.
    [diagnostico, trabajo].forEach((campo) => {
        campo.addEventListener('input', () => campo.setCustomValidity(''));
    });
}
