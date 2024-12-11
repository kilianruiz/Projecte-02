function validaNombre() {
    let nombre = document.getElementById("usuario").value; 
    let error_nombre = document.getElementById("error-nombre"); 

    if (nombre == null || nombre.length == 0) {
        error_nombre.innerHTML = "El nombre está vacío";
        return false;
    } else if (!isNaN(nombre)) {
        error_nombre.innerHTML = "El campo no debe contener números"; 
        return false;
    } else if (nombre.length < 3) { 
        error_nombre.innerHTML = "El nombre es demasiado corto";
        return false;
    } else {
        error_nombre.innerHTML = "";
        return true;
    }
}

function validaContraseña() {
    let contraseña = document.getElementById("password").value;
    let error_contraseña = document.getElementById("error_contraseña"); 

    if (contraseña == null || contraseña.length == 0 || /^\s+$/.test(contraseña)) { 
        error_contraseña.innerHTML = "Este campo no puede estar vacío.";
        return false;
    } else if (contraseña.length < 6) {
        error_contraseña.innerHTML = "La contraseña debe contener más de 6 caracteres.";
        return false;
    } else if (!(/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/).test(contraseña)) { 
        error_contraseña.innerHTML = "La contraseña debe contener al menos un número y una letra.";
        return false;
    } else {
        error_contraseña.innerHTML = "";
        return true;
    }
}

function validarFormulario() {
    let isNombreValido = validaNombre();
    let isContraseñaValida = validaContraseña();
    return isNombreValido && isContraseñaValida;
}

function validarFormulario2() {
    let isNombreValido = validaNombre();
    if (!isNombreValido) {
        return false;  // No enviar el formulario si el nombre no es válido
    }
    return true; 
}

document.getElementById('formMesa').addEventListener('submit', function(e) {
    let room_id = document.getElementById('room_id').value;
    let table_number = document.getElementById('table_number').value;
    let capacity = document.getElementById('capacity').value;

    if (!room_id || !table_number || !capacity) {
        alert('Por favor, complete todos los campos.');
        e.preventDefault();
        return;
    }

    // Comprobar si el número de mesa ya existe en la sala seleccionada
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'comprobarMesa.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        if (xhr.status == 200) {
            if (xhr.responseText == 'existe') {
                alert('El número de mesa ya está asignado en esta sala.');
                e.preventDefault();
            }
        }
    };
    xhr.send('table_number=' + table_number + '&room_id=' + room_id);
});
function validarFormulario() {
    let valid = true;

    // Limpiar mensajes de error previos
    document.getElementById('room_id_error').innerText = '';
    document.getElementById('table_number_error').innerText = '';
    document.getElementById('capacity_error').innerText = '';
    document.getElementById('status_error').innerText = '';
    document.getElementById('image_path_error').innerText = '';

    // Validar Sala
    const room_id = document.getElementById('room_id').value;
    if (!room_id) {
        document.getElementById('room_id_error').innerText = 'Por favor, seleccione una sala.';
        valid = false;
    }

    // Validar Número de Mesa
    const table_number = document.getElementById('table_number').value;
    if (!table_number || table_number <= 0) {
        document.getElementById('table_number_error').innerText = 'Por favor, ingrese un número de mesa válido.';
        valid = false;
    }

    // Validar Capacidad
    const capacity = document.getElementById('capacity').value;
    if (!capacity || capacity <= 0) {
        document.getElementById('capacity_error').innerText = 'Por favor, ingrese la capacidad de la mesa.';
        valid = false;
    }

    // Validar Estado
    const status = document.getElementById('status').value;
    if (!status) {
        document.getElementById('status_error').innerText = 'Por favor, seleccione un estado.';
        valid = false;
    }

    // Validar Imagen
    const image_path = document.querySelector('input[type="file"]').files[0];
    if (image_path && !['image/jpeg', 'image/png', 'image/gif'].includes(image_path.type)) {
        document.getElementById('image_path_error').innerText = 'Solo se permiten imágenes en formato JPEG, PNG o GIF.';
        valid = false;
    }

    return valid;
}

function validarFormulario3() {
    let valid = true;

    // Limpiar errores previos
    document.querySelectorAll('.error-message').forEach(function(errorDiv) {
        errorDiv.textContent = '';
    });

    // Validar nombre de la sala
    const nameRooms = document.getElementById('name_rooms').value.trim();
    if (nameRooms === '') {
        document.getElementById('name_rooms_error').textContent = 'El nombre de la sala es obligatorio.';
        valid = false;
    }

    // Validar capacidad
    const capacity = document.getElementById('capacity').value.trim();
    if (capacity === '' || isNaN(capacity) || capacity <= 0) {
        document.getElementById('capacity_error').textContent = 'La capacidad debe ser un número mayor que 0.';
        valid = false;
    }

    // Validar descripción
    const description = document.getElementById('description').value.trim();
    if (description === '') {
        document.getElementById('description_error').textContent = 'La descripción de la sala es obligatoria.';
        valid = false;
    }

    // Validar tipo de sala
    const roomType = document.getElementById('roomtype').value.trim();
    if (roomType === '') {
        document.getElementById('roomtype_error').textContent = 'Debes seleccionar un tipo de sala.';
        valid = false;
    }

    // Validar imagen (opcional)
    const imageFile = document.getElementById('image_file').files[0];
    if (imageFile && !['image/jpeg', 'image/png', 'image/gif'].includes(imageFile.type)) {
        document.getElementById('image_file_error').textContent = 'Solo se permiten archivos JPG, PNG o GIF.';
        valid = false;
    }

    return valid;
}

function validaForm4() {
    let valid = true;

    // Validar "Nombre de la Sala"
    const nameRooms = document.getElementById('name_rooms').value.trim();
    const nameRoomsError = document.getElementById('name_rooms_error');
    nameRoomsError.innerHTML = '';

    if (nameRooms === '') {
        nameRoomsError.innerHTML = 'El nombre de la sala es obligatorio.';
        valid = false;
    }

    // Validar "Seleccionar Sala"
    const roomId = document.getElementById('room_id').value;
    const roomIdError = document.getElementById('room_id_error');
    roomIdError.innerHTML = '';

    if (roomId === '') {
        roomIdError.innerHTML = 'Debes seleccionar una sala.';
        valid = false;
    }

    return valid;
}