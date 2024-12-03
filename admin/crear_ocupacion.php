<?php
session_start();

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Ocupación</title>
    <!-- Incluir Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #d9a875;
        }
        .card-header {
            background-color: #8A5021;
            color: white;
        }
        .btn-primary {
            background-color: #8A5021;
            border-color: #8A5021;
        }
        .btn-primary:hover {
            background-color: #6e3e19;
            border-color: #6e3e19;
        }
        .alert-danger {
            background-color: #8A5021;
            color: white;
            border: none;
        }
        .alert-success {
            background-color: #8A5021;
            color: white;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header text-center">
                        <h3>Crear Ocupación</h3>
                    </div>
                    <div class="card-body">
                        <form action="./valida_crear.php" method="POST">
                            <div class="mb-3">
                                <label for="numeroMesa" class="form-label">Número de la mesa</label>
                                <input type="number" name="numeroMesa" class="form-control" placeholder="Número de mesa" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nombreSala" class="form-label">Nombre de la sala:</label>
                                <select name="sala" id="sala" class="form-select" required>
                                    <option value="">Selecciona una sala</option>
                                    <?php
                                        foreach ($salas as $sala) {
                                            echo "<option value='" . htmlspecialchars($sala) . "' " . ($salaFiltro == $sala ? 'selected' : '') . ">" . htmlspecialchars($sala) . "</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="estadoSala" class="form-label">Estado de la sala:</label>
                                <select name="estadoSala" class="form-select" required>
                                    <option value="Ocupado">Ocupado</option>
                                    <option value="Libre">Libre</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="usuario" class="form-label">Nombre del camarero</label>
                                <select name="usuario" id="usuario" class="form-select" required>
                                    <option value="">Selecciona un camarero</option>
                                    <?php
                                    foreach ($usuarios as $usuario) {
                                        echo "<option value='" . htmlspecialchars($usuario) . "' " . ($usuarioFiltro == $usuario ? 'selected' : '') . ">" . htmlspecialchars($usuario) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fechaOcupacion" class="form-label">Fecha y hora de ocupación:</label>
                                <input type="datetime-local" name="fechaOcupacion" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fechaLiberacion" class="form-label">Fecha y hora de liberación:</label>
                                <input type="datetime-local" name="fechaLiberacion" class="form-control">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Crear</button>
                            </div>

                            <!-- Manejo de mensajes de error y éxito -->
                            <div class="mt-3">
                                <?php
                                    if (isset($_GET['error']) && $_GET['error'] == 'ocupada') {
                                        echo "<div class='alert alert-danger'>La mesa ya está ocupada. Por favor, elige otra mesa.</div>";
                                    } elseif (isset($_GET['success']) && $_GET['success'] == '1') {
                                        echo "<div class='alert alert-success'>Ocupación creada exitosamente.</div>";
                                    }
                                ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
