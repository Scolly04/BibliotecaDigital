
<?php
session_start();
require_once "conexion.php";

// Verificar el token de seguridad
if (
    $_SERVER["REQUEST_METHOD"] !== "POST" ||
    !isset($_POST["csrf_token"]) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    die("Solicitud no válida. Vuelve a la biblioteca.");
}

// Solo permitir solicitudes POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php");
    exit;
}

// Buscar la portada del libro
$consulta = mysqli_prepare(
    $conexion,
    "SELECT portada FROM libros WHERE id = ?"
);

mysqli_stmt_bind_param($consulta, "i", $id);
mysqli_stmt_execute($consulta);

$resultado = mysqli_stmt_get_result($consulta);
$libro = mysqli_fetch_assoc($resultado);

if (!$libro) {
    header("Location: index.php");
    exit;
}

// Eliminar el libro
$eliminar = mysqli_prepare(
    $conexion,
    "DELETE FROM libros WHERE id = ?"
);

mysqli_stmt_bind_param($eliminar, "i", $id);

if (mysqli_stmt_execute($eliminar)) {

    // Eliminar también la imagen local, si existe
    $portada = $libro["portada"];

 if (
    !empty($portada) &&
    strpos($portada, "imagenes/") === 0 &&
    is_file(__DIR__ . "/" . $portada)
) {
    unlink(__DIR__ . "/" . $portada);
}

    header("Location: index.php?mensaje=eliminado");
    exit;

} else {
    die("Error al eliminar el libro: " . mysqli_stmt_error($eliminar));
}
