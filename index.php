
<?php
session_start();
require_once "conexion.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$busqueda = trim($_GET['buscar'] ?? '');
$mensaje = $_GET['mensaje'] ?? '';


$categoriaSeleccionada = trim($_GET['categoria'] ?? '');


if ($busqueda !== '' && $categoriaSeleccionada !== '') {

    $consulta = mysqli_prepare(
        $conexion,
        "SELECT * FROM libros
         WHERE (titulo LIKE ? OR autor LIKE ?)
         AND categoria = ?
         ORDER BY titulo"
    );

    $filtro = "%" . $busqueda . "%";
    mysqli_stmt_bind_param(
        $consulta,
        "sss",
        $filtro,
        $filtro,
        $categoriaSeleccionada
    );

} elseif ($busqueda !== '') {

    $consulta = mysqli_prepare(
        $conexion,
        "SELECT * FROM libros
         WHERE titulo LIKE ? OR autor LIKE ?
         ORDER BY titulo"
    );

    $filtro = "%" . $busqueda . "%";
    mysqli_stmt_bind_param($consulta, "ss", $filtro, $filtro);

} elseif ($categoriaSeleccionada !== '') {

    $consulta = mysqli_prepare(
        $conexion,
        "SELECT * FROM libros
         WHERE categoria = ?
         ORDER BY titulo"
    );

    mysqli_stmt_bind_param($consulta, "s", $categoriaSeleccionada);

} else {

    $consulta = mysqli_prepare(
        $conexion,
        "SELECT * FROM libros ORDER BY titulo"
    );
}

mysqli_stmt_execute($consulta);
$resultado = mysqli_stmt_get_result($consulta);


mysqli_stmt_execute($consulta);
$resultado = mysqli_stmt_get_result($consulta);

$total = mysqli_query($conexion, "SELECT COUNT(*) AS cantidad FROM libros");

$cantidad = mysqli_fetch_assoc($total)['cantidad'];


function limpiar($dato) {
    return htmlspecialchars((string)$dato, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Biblioteca Digital</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f5eee4;
    color: #493526;
    font-family: Georgia, serif;
}

header {
    background: #68452f;
    color: #fffaf3;
    text-align: center;
    padding: 35px 20px;
}

header h1 {
    margin: 0;
    font-size: 36px;
}

header p {
    margin-bottom: 0;
    font-size: 17px;
}

.contenedor {
    width: 90%;
    max-width: 1500px;
    margin: 30px auto;
}

h2 {
    color: #68452f;
}

.tarjeta {
    background: #fffaf3;
    padding: 25px;
    margin-bottom: 25px;
    border-radius: 14px;
    border: 1px solid #e5d4c1;
    box-shadow: 0 4px 12px rgba(70, 45, 25, 0.08);
}

.contador {
    font-size: 42px;
    font-weight: bold;
    color: #9b704b;
}

label {
    display: block;
    margin: 14px 0 7px;
    font-weight: bold;
}

input, select {
    width: 100%;
    padding: 13px;
    border: 1px solid #d8c7b5;
    border-radius: 8px;
    font-size: 15px;
    background: white;
}

button {
    padding: 13px 22px;
    border: none;
    border-radius: 8px;
    background: #765239;
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background: #563a28;
}

.tarjeta form button {
    margin-top: 18px;
}

.buscador {
    display: grid;
    grid-template-columns: minmax(250px, 2fr) minmax(200px, 1fr) auto;
    gap: 12px;
    align-items: center;
    margin-bottom: 20px;
}

.buscador input,
.buscador select {
    width: 100%;
    min-width: 0;
    margin: 0;
    height: 55px;
}

.buscador button {
    height: 55px;
    white-space: nowrap;
}

.tabla-contenedor {
    overflow-x: auto;
    background: #fffaf3;
    border: 1px solid #e5d4c1;
    border-radius: 14px;
    box-shadow: 0 4px 12px rgba(70, 45, 25, 0.08);
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1050px;
}

thead {
    background: #eadbc9;
    color: #563a28;
}

th {
    padding: 20px 15px;
    text-align: left;
}

td {
    padding: 24px 15px;
    border-bottom: 1px solid #eadbc9;
    font-size: 18px;
}

tbody tr:nth-child(even) {
    background: #f8efe3;
}

.portada-libro {
    width: 112px;
    height: 165px;
    object-fit: cover;
    border-radius: 9px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

.disponible {
    color: #477b52;
    font-weight: bold;
}

.prestado {
    color: #b47732;
    font-weight: bold;
}

.acciones {
    min-width: 165px;
    text-align: center;
}

.boton-editar,
.boton-eliminar {
    display: block;
    width: 140px;
    margin: 5px auto;
    padding: 16px 12px;
    border: none;
    border-radius: 9px;
    background: #68452f;
    color: white !important;
    text-align: center;
    text-decoration: none !important;
    font-family: Arial, sans-serif;
    font-size: 17px;
    font-weight: bold;
    cursor: pointer;
}

.boton-editar:hover,
.boton-eliminar:hover {
    background: #563a28;
}

.acciones form {
    margin: 0;
}

.mensaje {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
}

.exito {
    background: #e2f1e4;
    color: #347044;
}

.error {
    background: #f8dddd;
    color: #9b2525;
}

@media (max-width: 650px) {
    .buscador {
        grid-template-columns: 1fr;
    }

    .buscador button {
        width: 100%;
    }

    header h1 {
        font-size: 28px;
    }
}

</style>
</head>

<body>

<header>
    <h1>📚 Biblioteca Digital</h1>
    <p>Tu espacio para descubrir y consultar libros</p>
</header>

<main class="contenedor">

    <?php if ($mensaje === 'exito'): ?>
    <div class="mensaje exito">¡Libro registrado correctamente! 📚</div>

<?php elseif ($mensaje === 'actualizado'): ?>
    <div class="mensaje exito">¡Libro actualizado correctamente! ✨</div>

<?php elseif ($mensaje === 'eliminado'): ?>
    <div class="mensaje exito">¡Libro eliminado correctamente! 🗑️</div>

<?php elseif ($mensaje === 'error'): ?>
    <div class="mensaje error">No se pudo registrar el libro. Revisa los datos.</div>
<?php endif; ?>

    <section class="tarjeta">
        <h2>Resumen de la biblioteca</h2>
        <div class="contador"><?php echo $cantidad; ?></div>
        <p>Libros registrados en nuestra base de datos.</p>
    </section>

    <section class="tarjeta">
        <h2>Registrar un libro</h2>

  
<form action="registrar.php" method="POST" enctype="multipart/form-data">

    <label>Título del libro</label>
    <input type="text" name="titulo" maxlength="150" required>

    <label>Autor</label>
    <input type="text" name="autor" maxlength="100" required>

    <label>Categoría</label>
    <input type="text" name="categoria" maxlength="80" required>

    <label>Año de publicación</label>
    <input type="number" name="anio" min="-3000"
           max="<?php echo date('Y'); ?>" required>

    <label>Estado</label>
    <select name="estado" required>
        <option value="">Selecciona un estado</option>
        <option value="Disponible">Disponible</option>
        <option value="Prestado">Prestado</option>
    </select>

    <label>Portada del libro</label>
    <input type="file" name="portada"
           accept="image/jpeg,image/png,image/webp">

    <button type="submit">Registrar libro</button>
</form>

    </section>

    <h2>Catálogo de libros</h2>

    
<form method="GET" class="buscador">

    <input type="text" name="buscar"
           placeholder="Buscar por título..."
           value="<?php echo limpiar($busqueda); ?>">

    <select name="categoria">
        <option value="">Todas las categorías</option>
        <option value="Novela" <?php echo $categoriaSeleccionada === 'Novela' ? 'selected' : ''; ?>>Novela</option>
        <option value="Clásico" <?php echo $categoriaSeleccionada === 'Clásico' ? 'selected' : ''; ?>>Clásico</option>
        <option value="Infantil" <?php echo $categoriaSeleccionada === 'Infantil' ? 'selected' : ''; ?>>Infantil</option>
        <option value="Fantasía" <?php echo $categoriaSeleccionada === 'Fantasía' ? 'selected' : ''; ?>>Fantasía</option>
        <option value="Romance" <?php echo $categoriaSeleccionada === 'Romance' ? 'selected' : ''; ?>>Romance</option>
        <option value="Terror" <?php echo $categoriaSeleccionada === 'Terror' ? 'selected' : ''; ?>>Terror</option>
        <option value="Ciencia ficción" <?php echo $categoriaSeleccionada === 'Ciencia ficción' ? 'selected' : ''; ?>>Ciencia ficción</option>
        <option value="Educativo" <?php echo $categoriaSeleccionada === 'Educativo' ? 'selected' : ''; ?>>Educativo</option>
    </select>

    <button type="submit">Buscar</button>

</form>


    <div class="tabla-contenedor">
        <table>
          <thead>
    <tr>
        <th>Portada</th>
        <th>Título</th>
        <th>Autor</th>
        <th>Categoría</th>
        <th>Año</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
</thead>

            <tbody>
            <?php if (mysqli_num_rows($resultado) > 0): ?>
                <?php while ($libro = mysqli_fetch_assoc($resultado)): ?>
                    <tr>
                        <td>
    <?php if (!empty($libro['portada'])): ?>
        <img
            src="<?php echo htmlspecialchars($libro['portada'], ENT_QUOTES, 'UTF-8'); ?>"
            alt="Portada de <?php echo limpiar($libro['titulo']); ?>"
            class="portada-libro"
        >
    <?php else: ?>
        Sin portada
    <?php endif; ?>
</td>
                        <td><?php echo limpiar($libro['titulo']); ?></td>
                        <td><?php echo limpiar($libro['autor']); ?></td>
                        <td><?php echo limpiar($libro['categoria']); ?></td>
                        <td><?php echo limpiar($libro['año']); ?></td>
                        <td class="<?php echo $libro['estado'] === 'Disponible' ? 'disponible' : 'prestado'; ?>">
                            <?php echo limpiar($libro['estado']); ?>
                        </td>
                        <td>
    <a class="boton-editar"
       href="editar.php?id=<?php echo (int)$libro['id']; ?>">
        Editar
    </a>

  <form action="eliminar.php" method="POST"
      onsubmit="return confirm('¿Estás segura de que deseas eliminar este libro?');">

    <input type="hidden" name="csrf_token"
           value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

        <input type="hidden" name="id"
               value="<?php echo (int)$libro['id']; ?>">

        <button type="submit" class="boton-eliminar">
            Eliminar
        </button>
    </form>
</td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No se encontraron libros.</td>
                </tr>
            <?php endif; ?>
            
            </tbody>
        </table>
    </div>

</main>
<script>
    setTimeout(function() {
        const mensajes = document.querySelectorAll('.mensaje');

        mensajes.forEach(function(mensaje) {
            mensaje.style.transition = 'opacity 0.5s';
            mensaje.style.opacity = '0';

            setTimeout(function() {
                mensaje.remove();
            }, 500);
        });
    }, 4000);
</script>
</body>
</html>
