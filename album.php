<?php
require_once('includes/conexion.inc.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discografía Grupo</title>
    <link rel="stylesheet" href="css/style.css">
    <?php

    //Se establecen las expresiones regulares en variables para posteriormente entender mejor el código
    $exprTitulo = '/^[A-z0-9\s\ñ]{3,15}$/';
    $exprDuracion = '/^\d+$/';
    $errores = null;

    //En caso de que se hayan enviado datos posteriormente tanto como para editar o añadir se realizarán estas comprobaciones
    if (count($_POST) != 0) {

        //Comprueba todos los datos para que ninguno esté vacío
        foreach ($_POST as $clave => $valor) {
            $valor = trim($valor);

            if (empty($valor)) {
                $errores[$clave] = '<p class="error">' . $clave . ' no puede estar vacío<p>';
            }
        }

        //Comprobaciones de los datos

        if (!preg_match($exprTitulo, $_POST["titulo"]) && !isset($errores["titulo"])) {
            $errores["titulo"] = '<p class="error">El titulo solo recibe de 3 a 15 letras, números y espacios.</p><br>';
        }

        if (!preg_match($exprDuracion, $_POST["duracion"]) && !isset($errores["duracion"])) {
            $errores["duracion"] = '<p class="error">La duración solo permite un valor entero en segundos.</p><br>';
        }

        //Si no hay errores se procede a realizar la inserción o actualización de los datos
        if (!$errores) {
            $conexion = conectar();

            //En caso de que se haya enviado el codigo de la canción se realizará un update, en caso contrario un insert
            if (!is_null($conexion)) {
                if (!isset($_POST['codigo'])) {

                    $consulta = $conexion->prepare('INSERT INTO canciones (titulo, album, duracion, posicion) VALUES (?, ?, ?, 0);');

                    $consulta->bindParam(1, $_POST["titulo"]);
                    $consulta->bindParam(2, $_GET["album"]);
                    $consulta->bindParam(3, $_POST["duracion"]);
                } else {

                    $consulta = $conexion->prepare('UPDATE canciones SET titulo=?, duracion=? WHERE codigo=?; ');

                    $consulta->bindParam(1, $_POST["titulo"]);
                    $consulta->bindParam(2, $_POST["duracion"]);
                    $consulta->bindParam(3, $_POST["codigo"]);
                }
            }

            try {
                $consulta->execute();
            } catch (\Throwable $th) {
                echo $th->getMessage();
            }

            unset($conexion);
            unset($consulta);
            header('Location: album.php?grupo=' . $_GET["grupo"] . '&album=' . $_GET['album']);
        }
    }

    //En caso de que se esté borrando un dato entrará aquí
    if (isset($_GET['accion']) && $_GET['accion'] == 'borrar') {
        $conexion = conectar();

        if (!is_null($conexion)) {
            $consulta = $conexion->prepare('DELETE FROM canciones WHERE codigo=?');

            $consulta->bindParam(1, $_GET["codigo"]);
        }
        try {
            $consulta->execute();
        } catch (\Throwable $th) {
        }

        unset($conexion);
        unset($consulta);
        header('Location: album.php?grupo=' . $_GET["grupo"] . '&album=' . $_GET['album']);
    }
    ?>
</head>

<body>
    <?php
    include_once('includes/cabecera.inc.php');

    //En caso de que se quiera borrar un album entrará en esta confirmación
    if (isset($_GET['accion']) && $_GET['accion'] == 'confirmar') {

        echo '<div class="borrar">';
        echo '  <h2>¿Estás seguro de que quieres borrar esa canción?</h2>';
        echo '  <a href="album.php?grupo=' . $_GET["grupo"] . '&album=' . $_GET["album"] . '&codigo=' . $_GET["codigo"] . '&accion=borrar">Borrar</a>';
        echo '</div>';
    }
    ?>
            <?php
            $conexion = conectar();

            if (!is_null($conexion)) {
                //Generación de las celdas de la información de las canciones
                $resultado = $conexion->query('SELECT codigo, titulo, duracion FROM canciones WHERE album=' . $_GET["album"] . ';');
                if ($resultado->rowCount()) {
                    echo '<div class="canciones">
                    <h1>Canciones</h1>
                    <table>
                        <tr>
                            <th>Título</th>
                            <th>Duración</th>
                            <th>Opciones</th>
                        </tr>';

                        while ($cancion = $resultado->fetch(PDO::FETCH_ASSOC)) {
                            //Se calculan los minutos y los segundos de la canción
                            $minutos = floor($cancion["duracion"] / 60);
                            $segundos = $cancion["duracion"] - ($minutos * 60);
                            $segundos = preg_match('/^\d{1}$/', $segundos) ? "0".$segundos : $segundos;
                            $cancion["duracion"] = $minutos . ":" . $segundos;

                            //Se muestra la información de la canción
                            echo '<tr>
                                    <td>' . $cancion["titulo"] . '</td>
                                    <td>' . $cancion["duracion"] . '</td>
                                    <td>
                                        <a href="album.php?grupo=' . $_GET["grupo"] . '&album=' . $_GET["album"] . '&codigo=' . $cancion["codigo"] . '"><img src="img/editar.png" alt="' . $cancion["titulo"] . '_icono_editar"></a>
                                        <a href="album.php?grupo=' . $_GET["grupo"] . '&album=' . $_GET["album"] . '&codigo=' . $cancion["codigo"] . '&accion=confirmar"><img src="img/borrar.png" alt="' . $cancion["titulo"] . '_icono_borrar"></a>
                                    </td>
                                  </tr>';
                        }
                    echo '  </table>';
                    echo '</div>';
                }

            }
            unset($resultado);
            unset($conexion);
            ?>
    <div>
        <?php
        $conexion = conectar();
        //En caso de que se haya enviado el codigo de la canción se mostrará el formulario con los datos de la misma
        if (!is_null($conexion) && isset($_GET['codigo'])) {
            $resultado = $conexion->query('SELECT * FROM canciones WHERE codigo=' . $_GET['codigo'] . ';');

            while ($cancion = $resultado->fetch(PDO::FETCH_ASSOC)) {
                foreach ($cancion as $key => $value) {
                    $_POST[$key] = $value;
                }
            }
            echo '<h1>Editar una canción</h1>';
        } else {
            echo '<h1>Añadir una canción</h1>';
        }

        unset($resultado);
        unset($conexion);


        ?>
        <form action="#" method="post">

            <label for="titulo">Titulo</label><br>
            <input type="text" name="titulo" id="titulo" value="<?= $_POST["titulo"] ?? "" ?>"><br>

            <?php echo isset($errores["titulo"]) ? $errores["titulo"] : "" ?>

            <label for="duracion">Duración (en segundos)</label><br>
            <input name="duracion" id="duracion" value="<?= $_POST["duracion"] ?? "" ?>"><br>

            <?php echo isset($errores["duracion"]) ? $errores["duracion"] : "" ?>

            <?php
            //En caso de que se haya enviado el codigo de la canción se almacenará en un input hidden
            if (isset($_GET['codigo'])) {
                echo '<input type="hidden" name="codigo" value="' . $_GET["codigo"] . '">';
                echo '<input type="submit" value="Editar">';
                echo '<a href="album.php?grupo=' . $_GET["grupo"] . "&album=" . $_GET["album"] . '">Cancelar</a>';
            } else {
                echo '<input type="submit" value="Añadir">';
            }
            ?>
        </form>

    </div>
</body>

</html>