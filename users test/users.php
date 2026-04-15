<?php
include '../backend/config/database.php';

$query = "SELECT * FROM USERS";
$stmt = $conn->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Users Management</title>

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f4f2fb;
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            height: 100vh;
            background: #2e145b;
            position: fixed;
            color: white;
            padding: 20px;
        }

        .sidebar a {
            display: block;
            color: #ddd;
            padding: 12px;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 6px;
        }

        .sidebar a.active {
            background: #6c3cff;
            color: white;
        }

        /* Main Content */
        .main {
            margin-left: 260px;
            padding: 30px;
        }

        h1 {
            margin-bottom: 5px;
        }

        .subtitle {
            color: #777;
        }

        /* Search */
        .search-box {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #ddd;
            margin: 20px 0;
        }

        /* Tabs */
        .tabs button {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            background: #eae6f9;
            cursor: pointer;
            margin-right: 6px;
        }

        .tabs .active {
            background: #6c3cff;
            color: white;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            font-size: 12px;
            color: #777;
        }

        td {
            padding: 12px 0;
            border-top: 1px solid #eee;
        }

        .status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
        }

        .ACTIVE {
            background: #d4f4dd;
            color: #2f7a44;
        }

        .INACTIVE {
            background: #fddede;
            color: #a32f2f;
        }

        /* Buttons */
        .btn {
            padding: 8px 14px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
        }

        .btn-add {
            background: #6c3cff;
            color: white;
        }

        .btn-delete {
            background: #ff4d4d;
            color: white;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>ONEQCU</h2>
        <a href="#">Dashboard</a>
        <a href="#">Assets</a>
        <a href="#">Borrow/Return</a>
        <a href="#">Maintenance</a>
        <a href="#">Reports</a>
        <a href="#">Departments</a>
        <a class="active" href="#">Users</a>
    </div>

    <div class="main">
        <h1>Users Management</h1>
        <p class="subtitle">Manage system users and their roles</p>

        <input class="search-box" placeholder="Search users by name, email, department, or role...">

        <div class="tabs">
            <button class="active">All Users</button>
            <button>Administrators</button>
            <button>Property Custodians</button>
            <button>Department Staff</button>
        </div>

        <br>

        <button class="btn btn-add" onclick="location.href='user_add.php'">+ Add User</button>

        <br><br>

        <div class="table-container">
            <table>
                <tr>
                    <th>USER ID</th>
                    <th>NAME</th>
                    <th>EMAIL</th>
                    <th>DEPARTMENT</th>
                    <th>ROLE</th>
                    <th>STATUS</th>
                    <th>LAST LOGIN</th>
                    <th>ACTIONS</th>
                </tr>

                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
                    <tr>
                        <td><?= htmlspecialchars($row['USER_ID']) ?></td>
                        <td><?= htmlspecialchars($row['FULL_NAME']) ?></td>
                        <td><?= htmlspecialchars($row['EMAIL']) ?></td>
                        <td><?= htmlspecialchars($row['DEPARTMENT']) ?></td>
                        <td><?= htmlspecialchars($row['ROLE']) ?></td>
                        <td>
                            <span class="status <?= strtolower($row['STATUS']) ?>">
                                <?= htmlspecialchars($row['STATUS']) ?>
                            </span>
                        </td>
                        <td>
                            <?= $row['LAST_LOGIN'] ? date('Y-m-d', strtotime($row['LAST_LOGIN'])) : '-' ?>
                        </td>
                        <td>
                            <a href="user_delete.php?id=<?= $row['USER_ID'] ?>" onclick="return confirm('Delete this user?')">
                                <button class="btn btn-delete">Delete</button>
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>

</body>

</html>