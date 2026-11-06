
<?php

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $titulo = trim($_POST["titulo"] ?? "");
    $autor = trim($_POST["autor"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "");
    $anio = $_POST["anio"] ?? "";
    $estado = $_POST["estado"] ?? "";

  // Validar campos obligatorios y longitud
if (
    $titulo === "" ||
    $autor === "" ||
    $categoria === "" ||
    $anio === "" ||
    $estado === "" ||
    mb_strlen($titulo) > 150 ||
    mb_strlen($autor) > 100 ||
    mb_strlen($categoria) > 80
) {
    header("Location: index.php?mensaje=error");
    exit;
}

    // Validar año
    if (
        filter_var($anio, FILTER_VALIDATE_INT) === false ||
        $anio < -3000 ||
        $anio > (int)date("Y")
    ) {
        header("Location: index.php?mensaje=error");
        exit;
    }

    // Validar estado
    if (!in_array($estado, ["Disponible", "Prestado"], true)) {
        header("Location: index.php?mensaje=error");
        exit;
    }

    // La portada es opcional
    $rutaPortada = null;
    $destino = null;

    if (isset($_FILES["portada"]) &&
        $_FILES["portada"]["error"] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES["portada"]["error"] !== UPLOAD_ERR_OK) {
            header("Location: index.php?mensaje=error");
            exit;
        }

        // Máximo 3 MB
        if ($_FILES["portada"]["size"] > 3 * 1024 * 1024) {
            header("Location: index.php?mensaje=error");
            exit;
        }

        // Comprobar imagen real
        $informacion = getimagesize($_FILES["portada"]["tmp_name"]);

        $informacion = getimagesize($_FILES["portada"]["tmp_name"]);

if (
    $informacion === false ||
    !isset($informacion["mime"])
) {
    header("Location: index.php?mensaje=error");
    exit;
}

        // Formatos permitidos
        $tiposPermitidos = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        ];

        $tipo = $informacion["mime"];

        if (!isset($tiposPermitidos[$tipo])) {
            header("Location: index.php?mensaje=error");
            exit;
        }

        // Guardar imagen
        $carpeta = __DIR__ . "/imagenes/";

        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        $nombreImagen = bin2hex(random_bytes(16)) . "." . $tiposPermitidos[$tipo];

        $destino = $carpeta . $nombreImagen;

        if (!move_uploaded_file($_FILES["portada"]["tmp_name"], $destino)) {
            header("Location: index.php?mensaje=error");
            exit;
        }

        $rutaPortada = "imagenes/" . $nombreImagen;
    }

    // Registrar libro
    $consulta = mysqli_prepare(
        $conexion,
        "INSERT INTO libros (titulo, autor, categoria, `año`, estado, portada)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$consulta) {
        if ($destino !== null && file_exists($destino)) {
            unlink($destino);
        }
        die("Error al preparar la consulta: " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param(
    $consulta,
    "sssiss",
        $titulo,
        $autor,
        $categoria,
        $anio,
        $estado,
        $rutaPortada
    );

    if (mysqli_stmt_execute($consulta)) {
        header("Location: index.php?mensaje=exito");
        exit;
    }

    if ($destino !== null && file_exists($destino)) {
        unlink($destino);
    }

    die("Error de MySQL: " . mysqli_stmt_error($consulta));
}

header("Location: index.php");
exit;

?>
