<header>
    <h1>Discografía</h1>
    <nav>
        <?php
        if (isset($_GET['grupo'])) {
            echo '<a href="index.php">Inicio</a> ';
        }
        if (isset($_GET['album'])) {
            echo '<a href="grupo.php?grupo=' . $_GET['grupo'] . '">Álbumes</a>';
        }
        ?>
        </ul>
    </nav>
</header>