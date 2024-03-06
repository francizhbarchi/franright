<?php

// Configuración
$max_files = 10;
$max_size = 30 * 1024 * 1024; // 30 MB

// Manejo de errores
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Validación de archivos
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'])) {
        $errors[] = "No se ha seleccionado ningún archivo.";
    } elseif (count($_FILES['files']['name']) > $max_files) {
        $errors[] = "No se pueden cargar más de $max_files archivos.";
    }

    foreach ($_FILES['files']['name'] as $key => $name) {
        if ($_FILES['files']['size'][$key] > $max_size) {
            $errors[] = "El archivo $name supera el tamaño máximo de 30 MB.";
        }

        if (!getimagesize($_FILES['files']['tmp_name'][$key])) {
            $errors[] = "El archivo $name no es una imagen válida.";
        }
    }

    // Si no hay errores, dividir y comprimir archivos
    if (empty($errors)) {
        $zip = new ZipArchive();
        $zip->open('archivos.zip', ZipArchive::CREATE);

        foreach ($_FILES['files']['name'] as $key => $name) {
            $tmp_name = $_FILES['files']['tmp_name'][$key];
            $parts = dividirImagen($tmp_name);

            foreach ($parts as $i => $part) {
                $zip->addFromString("$name-$i.jpg", $part);
            }
        }

        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="archivos.zip"');
        readfile('archivos.zip');
        unlink('archivos.zip');
    }
}

function dividirImagen($tmp_name) {
    $image = imagecreatefromjpeg($tmp_name);
    $width = imagesx($image);
    $height = imagesy($image);

    $partes = [];
    for ($i = 0; $i < $height; $i += 100) {
        for ($j = 0; $j < $width; $j += 100) {
            $parte = imagecreatetruecolor(100, 100);
            imagecopy($parte, $image, 0, 0, $j, $i, 100, 100);

            ob_start();
            imagejpeg($parte);
            $partes[] = ob_get_clean();

            imagedestroy($parte);
        }
    }

    imagedestroy($image);

    return $partes;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dividir archivos JPG</title>
</head>
<body>
    <h1>Dividir archivos JPG</h1>

    <?php if (isset($errors) && !empty($errors)): ?>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="index.php" method="post" enctype="multipart/form-data">
        <input type="file" name="files[]" multiple>
        <button type="submit">Dividir y comprimir</button>
    </form>
</body>
</html>
