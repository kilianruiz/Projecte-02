// Función para manejar la acción de ocupar o desocupar una mesa
function openTableOptions(tableId, status, romanTableId) {
    if (status === 'occupied') {
        // Si la mesa está ocupada, preguntar si se desea desocupar
        Swal.fire({
            title: '¿Deseas desocupar la mesa?',
            text: "Esta acción desocupará la mesa.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, desocupar mesa',
            cancelButtonText: 'No, cancelar',
        }).then((result) => {
            if (result.isConfirmed) {
                // Realizar la acción para desocupar la mesa
                occupyTable(tableId, 'vacate', romanTableId);  // Llamamos solo una vez
            }
        });
    } else {
        // Si la mesa está libre, preguntar si se desea ocupar
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción ocupará la mesa.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, ocupar mesa',
            cancelButtonText: 'No, cancelar',
        }).then((result) => {
            if (result.isConfirmed) {
                // Realizar la acción para ocupar la mesa
                occupyTable(tableId, 'occupy', romanTableId);  // Llamamos solo una vez
            }
        });
    }
}

// Función para hacer el insert y el update
function occupyTable(tableId, action, romanTableId) {  // Asegúrate de incluir romanTableId en la función
    const formData = new FormData();
    formData.append('tableId', tableId);
    formData.append('action', action); // Asegúrate de enviar la acción
    formData.append('romanTableId', romanTableId); // Pasamos romanTableId aquí también

    fetch('../salones/ocupar_mesa.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Verificamos que la respuesta sea válida
        if (data && data.includes('|')) {
            const result = data.split('|');
            const status = result[0];
            const actionType = result[1];

            // Asegurarse de que la respuesta es exitosa
            if (status === 'success') {
                const img = document.getElementById('imgMesa' + tableId);
                const tableElement = document.getElementById('mesa' + tableId);

                if (actionType === 'occupy') {
                    img.src = '../img/salonRoja.webp'; // Cambia la imagen a ocupada
                    tableElement.querySelector('p').textContent = 'Mesa ' + romanTableId + ' (Ocupada)';
                    
                    Swal.fire({
                        title: 'Mesa ocupada',
                        text: 'La mesa ha sido ocupada correctamente.',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    });
                } else if (actionType === 'vacate') {
                    img.src = '../img/salonVerde.webp'; // Cambia la imagen a libre
                    tableElement.querySelector('p').textContent = 'Mesa ' + romanTableId;
                    
                    Swal.fire({
                        title: 'Mesa desocupada',
                        text: 'La mesa ha sido desocupada correctamente.',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    });
                }
            } else {
                // Si la respuesta no es "success", no mostramos el SweetAlert de error
                console.error("Error: Respuesta inesperada del servidor.", data);
            }
        } else {
            // Si la respuesta no contiene los datos esperados, simplemente no hacemos nada
            console.error("Error: Respuesta del servidor no válida.", data);
        }
    })
    .catch(error => {
        // Si hay un error en la solicitud de fetch, solo lo registramos en consola
        console.error("Error en la solicitud:", error);
    });
}
