<?php
require_once "conexion.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    die("ID de libro no válido.");
}

// Buscar el libro
$consulta = mysqli_prepare($conexion, "SELECT * FROM libros WHERE id = ?");
mysqli_stmt_bind_param($consulta, "i", $id);
mysqli_stmt_execute($consulta);

$resultado = mysqli_stmt_get_result($consulta);
$libro = mysqli_fetch_assoc($resultado);

if (!$libro) {
    die("No se encontró el libro.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  
    $titulo = trim($_POST["titulo"] ?? "");
    $autor = trim($_POST["autor"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "");
    $anio = filter_var($_POST["anio"] ?? "", FILTER_VALIDATE_INT);
    $estado = $_POST["estado"] ?? "";

    if (
        $titulo === "" ||
        $autor === "" ||
        $categoria === "" ||
        mb_strlen($titulo) > 150 ||
        mb_strlen($autor) > 100 ||
        mb_strlen($categoria) > 80 ||
        $anio === false ||
        $anio < -3000 ||
        $anio > (int)date("Y") ||
        !in_array($estado, ["Disponible", "Prestado"], true)
    ) {
        $error = "Revisa los datos ingresados.";

    } else {

        $rutaPortada = $libro["portada"];
        $destino = null;

        if (
            isset($_FILES["portada"]) &&
            $_FILES["portada"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {
            if ($_FILES["portada"]["error"] !== UPLOAD_ERR_OK) {
                $error = "No se pudo subir la imagen.";

            } elseif ($_FILES["portada"]["size"] > 3 * 1024 * 1024) {
                $error = "La imagen no debe superar los 3 MB.";

            } else {
                $informacion = getimagesize($_FILES["portada"]["tmp_name"]);

                $extensiones = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                if (
                    $informacion === false ||
                    !isset($informacion["mime"]) ||
                    !isset($extensiones[$informacion["mime"]])
                ) {
                    $error = "Selecciona una imagen JPG, PNG o WEBP.";

                } else {
                    $carpeta = __DIR__ . "/imagenes/";

                    if (!is_dir($carpeta)) {
                        mkdir($carpeta, 0755, true);
                    }

                    $nombreImagen = bin2hex(random_bytes(16)) .
                        "." . $extensiones[$informacion["mime"]];

                    $destino = $carpeta . $nombreImagen;

                    if (move_uploaded_file($_FILES["portada"]["tmp_name"], $destino)) {
                        $rutaPortada = "imagenes/" . $nombreImagen;
                    } else {
                        $error = "No se pudo guardar la imagen.";
                    }
                }
            }
        }

        if ($error === "") {
            $actualizar = mysqli_prepare(
                $conexion,
                "UPDATE libros
                 SET titulo = ?, autor = ?, categoria = ?, `año` = ?, estado = ?, portada = ?
                 WHERE id = ?"
            );

            if (!$actualizar) {
                $error = "No se pudo preparar la actualización.";

            } else {
                mysqli_stmt_bind_param(
                    $actualizar,
                    "sssissi",
                    $titulo,
                    $autor,
                    $categoria,
                    $anio,
                    $estado,
                    $rutaPortada,
                    $id
                );

                if (mysqli_stmt_execute($actualizar)) {

                    if (
                        !empty($libro["portada"]) &&
                        $libro["portada"] !== $rutaPortada &&
                        strpos($libro["portada"], "imagenes/") === 0 &&
                        is_file(__DIR__ . "/" . $libro["portada"])
                    ) {
                        unlink(__DIR__ . "/" . $libro["portada"]);
                    }

                    header("Location: index.php?mensaje=actualizado");
                    exit;

                } else {
                    if ($destino !== null && is_file($destino)) {
                        unlink($destino);
                    }

                    $error = "Error al actualizar el libro.";
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar libro | Biblioteca Digital</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 40px 20px;
            background: #f5eee4;
            color: #493526;
            font-family: Arial, sans-serif;
        }

        .contenedor {
            max-width: 650px;
            margin: auto;
            padding: 35px;
            background: #fffdf9;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(70, 45, 25, 0.12);
        }

        h1 {
            text-align: center;
            color: #68452f;
            margin-top: 0;
        }

        .subtitulo {
            text-align: center;
            color: #8a7768;
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #d8c7b5;
            border-radius: 8px;
            background: white;
            font-size: 15px;
        }

        input:focus, select:focus {
            outline: 2px solid #b68b65;
        }

        .portada {
            display: block;
            width: 120px;
            height: 170px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 3px 8px #ddd;
        }

        .botones {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }

        button, .cancelar {
            flex: 1;
            padding: 13px;
            border: none;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
        }

        button {
            background: #765239;
            color: white;
        }

        button:hover {
            background: #563a28;
        }

        .cancelar {
            background: #e8ddd0;
            color: #493526;
        }

        .error {
            background: #f8dddd;
            color: #9b2525;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h1>📚 Editar libro</h1>
    <p class="subtitulo">Modifica la información del libro seleccionado.</p>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <label>Título:</label>
        <input type="text" name="titulo" required
               value="<?php echo htmlspecialchars($_POST["titulo"] ?? $libro["titulo"], ENT_QUOTES, "UTF-8"); ?>">

        <label>Autor:</label>
        <input type="text" name="autor" required
               value="<?php echo htmlspecialchars($_POST["autor"] ?? $libro["autor"], ENT_QUOTES, "UTF-8"); ?>">

        <label>Categoría:</label>
        <input type="text" name="categoria" required
               value="<?php echo htmlspecialchars($_POST["categoria"] ?? $libro["categoria"], ENT_QUOTES, "UTF-8"); ?>">

        <label>Año:</label>
        <input type="number" name="anio" required
               value="<?php echo htmlspecialchars($_POST["anio"] ?? $libro["año"], ENT_QUOTES, "UTF-8"); ?>">

        <label>Estado:</label>
        <select name="estado" required>
            <option value="Disponible"
                <?php echo ($_POST["estado"] ?? $libro["estado"]) === "Disponible" ? "selected" : ""; ?>>
                Disponible
            </option>
            <option value="Prestado"
                <?php echo ($_POST["estado"] ?? $libro["estado"]) === "Prestado" ? "selected" : ""; ?>>
                Prestado
            </option>
        </select>

        <label>Portada actual:</label>

        <?php if (!empty($libro["portada"])): ?>
            <img class="portada"
                 src="<?php echo htmlspecialchars($libro["portada"], ENT_QUOTES, "UTF-8"); ?>"
                 alt="Portada actual">
        <?php else: ?>
            <p>Este libro no tiene portada.</p>
        <?php endif; ?>

        <label>Seleccionar otra portada (opcional):</label>
        <input type="file" name="portada" accept="image/jpeg,image/png,image/webp">

        <div class="botones">
            <button type="submit">Guardar cambios</button>
            <a class="cancelar" href="index.php">Cancelar</a>
        </div>

    </form>

</div>

</body>
</html>
