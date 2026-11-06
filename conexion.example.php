<?php

$conexion = mysqli_connect(
    "sql112.infinityfree.com",
    "if0_43369734",
    "TU_CONTRASEÑA",
    "if0_43369734_biblioteca"
);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

mysqli_set_charset($conexion, "utf8mb4");

?>
