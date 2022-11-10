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
    $exprTitulo = '/^[A-z0-9\s\ñ\,]{3,25}$/';
    $exprPrecio = '/^\d+(\.\d{1,2})?$/';
    $exprAnyo = '/^\d{4}$/';
    $exprFecha = '/^\d{4}-\d{2}-\d{2}$/';
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

        if (!preg_match($exprTitulo, $_POST["titulo"]) && !isset($errores["titulo"])) {
            $errores["titulo"] = '<p class="error">El titulo solo recibe de 3 a 25 letras, números y espacios.</p><br>';
        }

        if (!preg_match($exprAnyo, $_POST["anyo"]) && !isset($errores["anyo"])) {
            $errores["anyo"] = '<p class="error">El año solo recibe un número entero de 4 dígitos.</p><br>';
        }

        if (!in_array($_POST["formato"], ["cd", "vinilo", "mp3", "dvd"]) && !isset($errores["formato"])) {
            $errores["formato"] = '<p class="error">El formato solo recibe: cd, vinilo, mp3 o dvd.</p><br>';
        }

        if (!preg_match($exprFecha, $_POST["fechacompra"]) && !isset($errores["fechacompra"])) {
            $errores["fechacompra"] = '<p class="error">La fecha de compra solo recibe una fecha de formato Año-Mes-Día.</p><br>';
        }

        if (!preg_match($exprPrecio, $_POST["precio"]) && !isset($errores["precio"])) {
            $errores["precio"] = '<p class="error">El precio solo admite números enteros y decimales de hasta 2 decimas.</p><br>';
        }

        if (!$errores) {
            $conexion = conectar();

            if (!is_null($conexion)) {
                if (!isset($_POST['codigo'])) {

                    $consulta = $conexion->prepare('INSERT INTO albumes (titulo, grupo, anyo, formato, fechacompra, precio) VALUES (?, ?, ?, ?, ?, ?);');

                    $consulta->bindParam(1, $_POST["titulo"]);
                    $consulta->bindParam(2, $_GET["grupo"]);
                    $consulta->bindParam(3, $_POST["anyo"]);
                    $consulta->bindParam(4, $_POST["formato"]);
                    $consulta->bindParam(5, $_POST["fechacompra"]);
                    $consulta->bindParam(6, $_POST["precio"]);
                } else {

                    $consulta = $conexion->prepare('UPDATE albumes SET titulo=?, anyo=?, formato=?, fechacompra=?, precio=? WHERE codigo=?; ');

                    $consulta->bindParam(1, $_POST["titulo"]);
                    $consulta->bindParam(2, $_POST["anyo"]);
                    $consulta->bindParam(3, $_POST["formato"]);
                    $consulta->bindParam(4, $_POST["fechacompra"]);
                    $consulta->bindParam(5, $_POST["precio"]);
                    $consulta->bindParam(6, $_POST["codigo"]);
                }
            }

            try {
                $consulta->execute();
            } catch (\Throwable $th) {
            }

            unset($conexion);
            unset($consulta);
            header('Location: grupo.php?grupo=' . $_GET["grupo"]);
        }
    }

    //En caso de que se esté borrando un dato entrará aquí
    if (isset($_GET['accion'])) {
        $conexion = conectar();

        if (!is_null($conexion)) {
            $consulta = $conexion->prepare('DELETE FROM albumes WHERE codigo=?');

            $consulta->bindParam(1, $_POST["codigo"]);
        }
        try {
            $consulta->execute();
        } catch (\Throwable $th) {
        }

        unset($conexion);
        unset($consulta);
        header('Location: grupo.php?grupo=' . $_GET["grupo"]);
    }
    ?>
</head>

<body>
    <?php
    include_once('includes/cabecera.inc.php');
    ?>
    <div class="album">
        <ul>
            <?php
            $conexion = conectar();

            if (!is_null($conexion)) {
                $resultado = $conexion->query('SELECT codigo, titulo FROM albumes WHERE grupo=' . $_GET["grupo"] . ';');

                while ($album = $resultado->fetch(PDO::FETCH_ASSOC)) {
                    echo '<li>
                            <a href="album.php?grupo=' . $_GET["grupo"] . '&album=' . $album["codigo"] . '">' . " " . $album["titulo"] . '</a>
                            <a href="grupo.php?grupo=' . $_GET["grupo"] . '&codigo=' . $album["codigo"] . '"><img src="img/editar.png" alt="' . $album["titulo"] . '_icono_editar"></a>
                            <a href="grupo.php?grupo=' . $_GET["grupo"] . '&codigo=' . $album["codigo"] . '&accion=borrar"><img src="img/borrar.png" alt="' . $album["titulo"] . '_icono_borrar"></a>
                          </li>';
                }
            }

            unset($resultado);
            unset($conexion);
            ?>
        </ul>
    </div>
    <div>
        <?php
        $conexion = conectar();

        if (!is_null($conexion) && isset($_GET['codigo'])) {
            $resultado = $conexion->query('SELECT * FROM albumes WHERE codigo=' . $_GET['codigo'] . ';');

            while ($grupo = $resultado->fetch(PDO::FETCH_ASSOC)) {
                foreach ($grupo as $key => $value) {
                    $_POST[$key] = $value;
                }
            }
            echo '<h1>Editar un album</h1>';
        } else {
            echo '<h1>Añadir un album</h1>';
        }

        unset($resultado);
        unset($conexion);

        ?>
        <form action="#" method="post">

            <label for="titulo">Titulo</label><br>
            <input type="text" name="titulo" id="titulo" value="<?= $_POST["titulo"] ?? "" ?>"><br>

            <?php echo isset($errores["titulo"]) ? $errores["titulo"] : "" ?>

            <label for="precio">Precio</label><br>
            <input name="precio" id="precio" value="<?= $_POST["precio"] ?? "" ?>"><br>

            <?php echo isset($errores["precio"]) ? $errores["precio"] : "" ?>

            <label for="formato">Formato</label><br>
            <select name="formato" id="formato">
                <option value="cd">CD</option>
                <option value="vinilo">Vinilo</option>
                <option value="dvd">DVD</option>
                <option value="mp3">MP3</option>
            </select>
            <br><br>

            <?php echo isset($errores["formato"]) ? $errores["formato"] : "" ?>

            <label for="anyo">Año</label><br>
            <input name="anyo" id="anyo" value="<?= $_POST["anyo"] ?? "" ?>"><br>

            <?php echo isset($errores["anyo"]) ? $errores["anyo"] : "" ?>

            <label for="fechacompra">Fecha de la compra</label><br>
            <input name="fechacompra" id="fechacompra" value="<?= $_POST["fechacompra"] ?? "" ?>"><br>

            <?php echo isset($errores["fechacompra"]) ? $errores["fechacompra"] : "" ?>

            <input type="hidden" name="codigo" value="<?= $_GET["codigo"] ?? "" ?>">

            <?php
            if (isset($_GET['codigo'])) {
                echo '<input type="submit" value="Editar">';
                echo '<a href="grupo.php?grupo=' . $_GET["grupo"] . '">Cancelar</a>';
            } else {
                echo '<input type="submit" value="Añadir">';
            }
            ?>
        </form>

    </div>
</body>

</html>