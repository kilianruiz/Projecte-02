function openTableOptions(tableId, status, romanTableId) {
    if (status === 'reserved') {
        Swal.fire({
            title: `Mesa ${romanTableId} ya está reservada`,
            text: 'No puedes hacer una nueva reserva en este horario.',
            icon: 'error'
        });
        return;
    }

    // Mostrar el modal
    document.getElementById('availabilityModal').style.display = 'block';
    document.getElementById('tableId').value = tableId;
}

// Cerrar el modal
document.querySelector('.close').onclick = function () {
    document.getElementById('availabilityModal').style.display = 'none';
}
