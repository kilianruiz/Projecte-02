<?php
session_start();

if (isset($_GET['room_id'])) {
    $roomId = $_GET['room_id'];
    include('../conexion/conexion.php');
    
    if (!$conexion) {
        echo "Error de conexión a la base de datos.";
        exit();
    }

    $sql = "SELECT room_id, name_rooms FROM tbl_rooms WHERE room_id = :room_id";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':room_id', $roomId, PDO::PARAM_INT);
    $stmt->execute();
    $sala = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sala) {
        echo "Sala no encontrada.";
        exit();
    }

    $sqlTables = "SELECT table_id, status FROM tbl_tables WHERE room_id = :room_id";
    $stmtTables = $conexion->prepare($sqlTables);
    $stmtTables->bindParam(':room_id', $roomId, PDO::PARAM_INT);
    $stmtTables->execute();
    $tables = $stmtTables->fetchAll(PDO::FETCH_ASSOC);
    
} else {
    echo "No se seleccionó ninguna sala.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($sala['name_rooms']) ?></title>
    <link rel="stylesheet" href="../styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .table {
            display: inline-block;
            margin: 10px;
            text-align: center;
            width: 100px;
        }
        .table img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }
        .table p {
            margin-top: 5px;
        }
        .tables-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }.modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #A67C52; /* Marrón clarito */
    color: #FFFFFF; /* Letras blancas */
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25); /* Sombra suave */
    width: 80%;
    max-width: 500px;
    z-index: 1000;
    animation: fadeIn 0.3s ease-in-out;
}

.modal.active {
    display: block;
}

.modal .close {
    float: right;
    font-size: 20px;
    font-weight: bold;
    color: #FFFFFF; /* Blanco */
    cursor: pointer;
    transition: color 0.3s;
}

.modal .close:hover {
    color: #FFD1C1; /* Color más claro para el hover */
}

.modal h2,
.modal h3 {
    font-size: 24px;
    margin-bottom: 15px;
    color: #FFFFFF; /* Blanco */
}

#reservationForm label {
    font-size: 14px;
    font-weight: bold;
    color: #FFFFFF; /* Blanco */
    margin-top: 10px;
    display: block;
}

#reservationForm input,
#reservationForm select {
    width: 100%;
    padding: 10px;
    margin: 5px 0 15px;
    border: 1px solid #6c3e18;
    border-radius: 5px;
    font-size: 14px;
    background: #FFFFFF; /* Fondo blanco */
    color: #333333; /* Texto oscuro */
}

#reservationForm button {
    background: #6c3e18;
    color: #FFFFFF;
    font-size: 16px;
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s ease-in-out;
}

#reservationForm button:hover {
    background: #8A5021;
}

#reservationList ul {
    list-style-type: none;
    padding: 0;
    margin: 10px 0;
}

#reservationList li {
    background: #8A5021; /* Marrón oscuro */
    padding: 10px;
    margin: 5px 0;
    border-radius: 5px;
    font-size: 14px;
    color: #FFFFFF; /* Blanco */
}


    </style>
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($sala['name_rooms']) ?></h1>

        <div class="tables-container">
            <?php foreach ($tables as $row): ?>
                <?php
                $tableId = $row['table_id'];
                $status = $row['status'];
                $imgSrc = ($status === 'occupied') ? '../img/salonRoja.webp' : '../img/salonVerde.webp';
                ?>
                <div class="table" onclick="openReservationModal(<?= $tableId ?>)">
                    <img src="<?= $imgSrc ?>" alt="Mesa <?= $tableId ?>">
                    <p>Mesa <?= $tableId ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <button class="logout-button" onclick="window.history.back()">Volver</button>
    </div>

    <!-- Modal para gestionar reservas -->
    <div id="reservationModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Reservas de la mesa <span id="modalTableId"></span></h2>
        <div id="reservationList"></div>
        <form id="reservationForm" onsubmit="return saveReservation(event)">
            <h3>Agregar nueva reserva</h3>
            <input type="hidden" id="tableId" name="table_id">
            <label>Nombre:</label>
            <input type="text" id="customerName" name="customer_name" required>
            <label>Fecha:</label>
            <input type="date" id="reservationDate" name="reservation_date" required>
            <label>Hora:</label>
            <select id="reservationTime" name="reservation_time" required>
                <option value="13:00">13:00</option>
                <option value="15:00">15:00</option>
                <option value="20:00">20:00</option>
                <option value="22:00">22:00</option>
            </select>
            <button type="submit">Reservar</button>
        </form>
    </div>

    <script>
function openReservationModal(tableId) {
    document.getElementById('reservationModal').classList.add('active');
    document.getElementById('modalTableId').textContent = tableId;
    document.getElementById('tableId').value = tableId;

    // Llamada para obtener las reservas de la mesa
    fetch(`./getReservations.php?table_id=${tableId}`)
        .then(response => response.json())
        .then(data => {
            const reservationList = document.getElementById('reservationList');
            reservationList.innerHTML = ''; // Limpiar la lista de reservas antes de agregar nuevas

            if (data.error) {
                reservationList.innerHTML = `<p>Error al cargar las reservas: ${data.error}</p>`;
            } else {
                reservationList.innerHTML = '<ul>';
                data.forEach(reservation => {
                    reservationList.innerHTML += `<li>${reservation.customer_name} - ${reservation.reservation_date} ${reservation.reservation_time}</li>`;
                });
                reservationList.innerHTML += '</ul>';
            }
        })
        .catch(error => {
            console.error('Error al cargar las reservas:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error al cargar las reservas',
                text: 'No se pudo cargar la lista de reservas. Inténtalo nuevamente.',
            });
        });
}


        function closeModal() {
            document.getElementById('reservationModal').classList.remove('active');
        }

        function saveReservation(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('reservationForm'));
    
    fetch('./saveReservations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())  // Cambiado a .json() para manejar respuestas JSON
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Reserva guardada',
                text: data.message,
            });
            closeModal();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo guardar la reserva. Inténtalo nuevamente.',
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo guardar la reserva. Inténtalo nuevamente.',
        });
    });
}


        // Validaciones de fecha para prevenir selecciones inválidas
        document.addEventListener('DOMContentLoaded', function () {
            const dateInput = document.querySelector('#reservationDate');

            if (dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.setAttribute('min', today);
            }
        });
    </script>
</body>
</html>