<?php
$host = '127.0.0.1';
//$port = ;
$user = 'root';
$password = '';

$conn = new mysqli($host, $user, $password, '');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$dbs = $conn->query("SHOW DATABASES");

function showStructure($conn, $db, $table)
{
    echo "<div class='table-container'>";
    echo "<div class='card mb-4'><div class='card-header bg-dark text-white'>
            <strong>Table: $table (Database: $db)</strong>
          </div><div class='card-body'>";

    // Show table structure
    $desc = $conn->query("DESCRIBE `$table`");
    if ($desc) {
        echo "<div class='table-responsive'><table class='table table-bordered'>
                <thead class='table-light'>
                    <tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>
                </thead><tbody>";
        while ($row = $desc->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td style='font-family: \"Courier New\", Courier, monospace;'>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</tbody></table></div>";
    }

    // Show foreign key relations
    $fkQuery = "
        SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = '$table' AND REFERENCED_TABLE_NAME IS NOT NULL";

    $fks = $conn->query($fkQuery);
    if ($fks && $fks->num_rows > 0) {
        echo "<h5>🔗 Foreign Keys</h5>";
        echo "<table class='table table-sm table-striped'>";
        echo "<thead><tr><th>Column</th><th>References Table</th><th>References Column</th></tr></thead><tbody>";
        while ($fk = $fks->fetch_assoc()) {
            echo "<tr>
                    <td style='font-family: \"Courier New\", Courier, monospace;'>{$fk['COLUMN_NAME']}</td>
                    <td>{$fk['REFERENCED_TABLE_NAME']}</td>
                    <td>{$fk['REFERENCED_COLUMN_NAME']}</td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p class='text-muted fst-italic'>No foreign key relations.</p>";
    }

    echo "</div></div></div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Database Table Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            padding: 30px;
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        code, pre, td {
            font-family: "Courier New", Courier, monospace;
        }
        .table-container {
            margin-bottom: 40px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <h2 class="mb-4">📋 Database Table Viewer</h2>

    <div class="card mb-4">
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-4">
                    <label for="database" class="form-label">Select Database</label>
                    <select name="database" id="database" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Choose a database --</option>
                        <?php
                        $dbs->data_seek(0);
                        while ($row = $dbs->fetch_assoc()) {
                            $dbName = $row['Database'];
                            $selected = (isset($_POST['database']) && $_POST['database'] === $dbName) ? 'selected' : '';
                            echo "<option value=\"$dbName\" $selected>$dbName</option>";
                        }
                        ?>
                    </select>
                </div>

                <?php if (!empty($_POST['database']) && empty($_POST['show_all'])): ?>
                    <div class="col-md-4">
                        <label for="table" class="form-label">Select Table</label>
                        <select name="table" id="table" class="form-select">
                            <?php
                            $selectedDb = $_POST['database'];
                            $conn->select_db($selectedDb);
                            $tables = $conn->query("SHOW TABLES");
                            while ($row = $tables->fetch_row()) {
                                $tableName = $row[0];
                                $selectedTable = ($_POST['table'] ?? '') === $tableName ? 'selected' : '';
                                echo "<option value=\"$tableName\" $selectedTable>$tableName</option>";
                            }
                            ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-md-2 align-self-end">
                    <div class="form-check">
                        <input type="checkbox" name="show_all" value="1" class="form-check-input" id="showAll"
                            <?php if (!empty($_POST['show_all'])) echo 'checked'; ?>
                            onchange="this.form.submit()">
                        <label class="form-check-label" for="showAll">Show all tables</label>
                    </div>
                </div>

                <div class="col-md-2 align-self-end">
                    <input type="submit" class="btn btn-primary w-100" value="Show Structure" />
                </div>
            </form>
        </div>
    </div>

    <?php
    if (!empty($_POST['database'])):
        $selectedDb = $_POST['database'];
        $conn->select_db($selectedDb);

        echo "<div class='mb-4'><h4>🗄️ Viewing Structure of Database: <code>$selectedDb</code></h4></div>";

        if (!empty($_POST['show_all'])):
            $allTables = $conn->query("SHOW TABLES");
            while ($row = $allTables->fetch_row()) {
                showStructure($conn, $selectedDb, $row[0]);
            }
        elseif (!empty($_POST['table'])):
            showStructure($conn, $selectedDb, $_POST['table']);
        endif;
    endif;

    $conn->close();
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
