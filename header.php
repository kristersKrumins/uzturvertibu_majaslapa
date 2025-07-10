<?php
require 'Database/db.php';
function renderheader(){
global $conn;
?>
        <div id="dropdownContent" class="dropdown-content">
            <a href="index.php">Sākums</a>
            <a href="menu.php">Ēdienkarte</a>
            <a href="profile.php">Profils</a>
            <a href="history.php">Vēsture</a>
        </div>
    <header>
        <div class="logo"><h1>Ēdienkartes Optimizēšana</h1></div>
        <div class="dropdown">
            <button id="dropdownButton" class="dropbtn">☰</button>
        </div>
        <div class="nav">
            <a href="index.php"><button>Sākums</button></a>
            <a href="menu.php"><button>Ēdienkarte</button></a>
            <a href="profile.php"><button>Profils</button></a>
            <a href="history.php"><button>Vēsture</button></a>
        </div>
        <div class="user">
            <form action="Functions/logout.php" method="post" >
                <button name="submit" type="submit">Izrakstīties</button>
            </form>
            <?php
            $sql = "SELECT * FROM users WHERE ID = '" . $_SESSION["id"] . "'";
            $result = mysqli_query($conn, $sql);
            $row = mysqli_fetch_assoc($result);

            echo "<p><a>Lietotājs: </a> {$row['USERNAME']}</p>";

            // Determine photo source
            $profilePhotoSrc = !empty($row['PROFILE_PHOTO']) ? $row['PROFILE_PHOTO'] : 'bildes/6.jpg';
            
            echo "<img src='{$profilePhotoSrc}' alt='Profile Photo' class='profile-photo'>";
            ?>
        </div>
    </header>
    <script>
    document.getElementById('dropdownButton').addEventListener('click', function() {
        var dropdownContent = document.getElementById('dropdownContent');
        var dropdownButton = document.getElementById('dropdownButton');
        if (dropdownContent.classList.contains('show')) {
            dropdownContent.classList.remove('show');
            dropdownContent.classList.add('hide');
            dropdownButton.classList.remove('active-btn');
        } else {
            dropdownContent.classList.remove('hide');
            dropdownContent.classList.add('show');
            dropdownButton.classList.add('active-btn');
        }
    });
</script>
<?php
}
?>