<?php
require_once('includes/conexion.inc.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discografía</title>
    <link rel="stylesheet" href="css/style.css">
    <?php

    //Se establecen las expresiones regulares en variables para posteriormente entender mejor el código
    $exprNombre = '/^[A-z0-9\s\ñ]{3,15}$/';
    $exprGenero = '/^[A-z\s\ñ]{3,15}$/';
    $exprPais = '/^[A-z\ñ]{2,}$/';
    $exprInicio = '/^\d{4}$/';
    $errores = null;

    //En caso de que se hayan enviado datos posteriormente se realizarán estas comprobaciones
    if (count($_POST) != 0) {

        //Comprueba todos los datos para que ninguno esté vacío
        foreach ($_POST as $clave => $valor) {
            $valor = trim($valor);

            if (empty($valor)) {
                $errores[$clave] = '<p class="error">' . $clave . ' no puede estar vacío<p>';
            }
        }

        //Comprobaciones de los datos

        if (!preg_match($exprNombre, $_POST["nombre"]) && !isset($errores["nombre"])) {
            $errores["nombre"] = '<p class="error">El nombre solo recibe de 3 a 15 letras y números.</p><br>';
        }

        if (!preg_match($exprGenero, $_POST["genero"]) && !isset($errores["genero"])) {
            $errores["genero"] = '<p class="error">El género solo admite entre 3 a 15 letras.</p><br>';
        }

        if (!preg_match($exprPais, $_POST["pais"]) && !isset($errores["pais"])) {
            $errores["pais"] = '<p class="error">El país solo recibe un mínimo de 2 letras.</p><br>';
        }

        if (!preg_match($exprInicio, $_POST["inicio"]) && !isset($errores["inicio"])) {
            $errores["inicio"] = '<p class="error">El año de inicio solo recibe un número.</p><br>';
        }



        //Si no hay errores se procede a realizar la inserción o actualización de los datos
        if (!$errores) {
            $conexion = conectar();
            
            //En caso de que se haya enviado el codigo del grupo se realizará un update, en caso contrario un insert
            if (!is_null($conexion)) {
                if (!isset($_POST['codigo'])) {

                    $consulta = $conexion->prepare('INSERT INTO grupos (nombre, genero, pais, inicio) VALUES (?, ?, ?, ?); ');

                    $consulta->bindParam(1, $_POST["nombre"]);
                    $consulta->bindParam(2, $_POST["genero"]);
                    $consulta->bindParam(3, $_POST["pais"]);
                    $consulta->bindParam(4, $_POST["inicio"]);
                } else {

                    $consulta = $conexion->prepare('UPDATE grupos SET nombre=?, genero=?, pais=?, inicio=? WHERE codigo=?; ');

                    $consulta->bindParam(1, $_POST["nombre"]);
                    $consulta->bindParam(2, $_POST["genero"]);
                    $consulta->bindParam(3, $_POST["pais"]);
                    $consulta->bindParam(4, $_POST["inicio"]);
                    $consulta->bindParam(5, $_POST["codigo"]);
                }
            }
            try {
                $consulta->execute();
            } catch (\Throwable $th) {
            }

            unset($conexion);
            unset($consulta);
            header('Location: index.php');
        }
    }

    //En caso de que se esté borrando un dato entrará aquí
    if (isset($_GET['accion']) && $_GET['accion'] == 'borrar') {
        $conexion = conectar();

        if (!is_null($conexion)) {
            $consulta = $conexion->prepare('DELETE FROM grupos WHERE codigo=?');

            $consulta->bindParam(1, $_GET["codigo"]);
        }
        try {
            $consulta->execute();
        } catch (\Throwable $th) {
        }

        unset($conexion);
        unset($consulta);

        header('Location: index.php');
    }

    ?>
</head>

<body>
    <?php
    include_once('includes/cabecera.inc.php');

    //En caso de que se quiera borrar un grupo entrará en esta confirmación
    if (isset($_GET['accion']) && $_GET['accion'] == 'confirmar') {
        echo '<div class="borrar">';
        echo '  <h2>¿Estás seguro de que quieres borrar este grupo?</h2>';
        echo '  <a href="index.php?codigo=' . $_GET["codigo"] . '&accion=borrar"">Borrar</a>';
        echo '</div>';
    }
    ?>
    <div class="grupos">
        <ol>
            <?php
            //Generación de la lista de grupos
            $conexion = conectar();

            if (!is_null($conexion)) {
                $resultado = $conexion->query('SELECT codigo, nombre FROM grupos;');

                while ($grupo = $resultado->fetch(PDO::FETCH_ASSOC)) {
                    echo '<li>
                            <a href="grupo.php?grupo=' . $grupo["codigo"] . '">' . " " . $grupo["nombre"] . '</a>
                            <a href="index.php?codigo=' . $grupo["codigo"] . '"><img src="img/editar.png" alt="' . $grupo["nombre"] . '_icono_editar"></a>
                            <a href="index.php?codigo=' . $grupo["codigo"] . '&accion=confirmar""><img src="img/borrar.png" alt="' . $grupo["nombre"] . '_icono_borrar"></a>
                        </li>';
                }
            }
            unset($resultado);
            unset($conexion);
            ?>
        </ol>
    </div>
    <div>
        <?php
        //En caso de que se haya enviado el codigo del grupo se mostrará el formulario con los datos del grupo
        $conexion = conectar();

        if (!is_null($conexion) && isset($_GET['codigo'])) {
            $resultado = $conexion->query('SELECT * FROM grupos WHERE codigo=' . $_GET['codigo'] . ';');

            while ($grupo = $resultado->fetch(PDO::FETCH_ASSOC)) {
                foreach ($grupo as $key => $value) {
                    $_POST[$key] = $value;
                }
            }
            echo '<h1>Editar un grupo</h1>';
        } else {
            echo '<h1>Añadir un grupo</h1>';
        }

        unset($resultado);
        unset($conexion);


        ?>
        <form action="#" method="post">

            <label for="nombre">Nombre</label><br>
            <input type="text" name="nombre" id="nombre" value="<?= $_POST["nombre"] ?? "" ?>"><br>

            <?php echo isset($errores["nombre"]) ? $errores["nombre"] : "" ?>

            <label for="genero">Género</label><br>
            <input name="genero" id="genero" value="<?= $_POST["genero"] ?? "" ?>"><br>

            <?php echo isset($errores["genero"]) ? $errores["genero"] : "" ?>

            <label for="pais">País</label><br>
            <input name="pais" id="pais" value="<?= $_POST["pais"] ?? "" ?>"><br>

            <?php echo isset($errores["pais"]) ? $errores["pais"] : "" ?>

            <label for="inicio">Año de inicio</label><br>
            <input name="inicio" id="inicio" value="<?= $_POST["inicio"] ?? "" ?>"><br>

            <?php echo isset($errores["inicio"]) ? $errores["inicio"] : "" ?>
            <?php
            //En caso de que se haya enviado el codigo del grupo se almacenará en un input hidden
            if (isset($_GET['codigo'])) {
                echo '<input type="hidden" name="codigo" value="' . $_GET["codigo"] . '">';
                echo '<input type="submit" value="Editar">';
                echo '<a href="index.php">Cancelar</a>';
            } else {
                echo '<input type="submit" value="Añadir">';
            }
            ?>
        </form>

    </div>
</body>

</html>