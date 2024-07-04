<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tailwebs";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Edit and Save Actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['edit']) && isset($_POST['id'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $subject = $_POST['subject'];
        $mark = $_POST['mark'];

        $sql = "UPDATE students SET name='$name', subject='$subject', mark='$mark' WHERE id='$id'";
        if ($conn->query($sql) === TRUE) {
            // Redirect to avoid resubmission on refresh
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Error updating record: " . $conn->error;
        }
    } elseif (isset($_POST['delete']) && isset($_POST['id'])) {
        $id = $_POST['id'];

        $sql = "DELETE FROM students WHERE id='$id'";
        if ($conn->query($sql) === TRUE) {
            // Redirect to avoid resubmission on refresh
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Error deleting record: " . $conn->error;
        }
    } elseif (isset($_POST['add'])) {
        $newName = $_POST['new-name'];
        $newSubject = $_POST['new-subject'];
        $newMark = $_POST['new-mark'];

        // Check if a student with the same name and subject exists
        $checkSql = "SELECT * FROM students WHERE name='$newName' AND subject='$newSubject'";
        $checkResult = $conn->query($checkSql);

        if ($checkResult->num_rows > 0) {
            // Student with same name and subject exists, update the marks
            $existingStudent = $checkResult->fetch_assoc();
            $existingId = $existingStudent['id'];
            $existingMark = $existingStudent['mark'];

            $updatedMark = $existingMark + $newMark;

            $updateSql = "UPDATE students SET mark='$updatedMark' WHERE id='$existingId'";
            if ($conn->query($updateSql) === TRUE) {
                // Redirect to avoid resubmission on refresh
                header("Location: ".$_SERVER['PHP_SELF']);
                exit;
            } else {
                echo "Error updating record: " . $conn->error;
            }
        } else {
            // No existing student found, create a new record
            $insertSql = "INSERT INTO students (name, subject, mark) VALUES ('$newName', '$newSubject', '$newMark')";
            if ($conn->query($insertSql) === TRUE) {
                // Redirect to avoid resubmission on refresh
                header("Location: ".$_SERVER['PHP_SELF']);
                exit;
            } else {
                echo "Error inserting record: " . $conn->error;
            }
        }
    }
}

// Fetch all students for display
$sql = "SELECT id, name, subject, mark FROM students";
$result = $conn->query($sql);

$students = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Portal</title>
  <link rel="stylesheet" href="styles.css">
  
  <!-- Add Font Awesome CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-0/7/9p8A4jBsF7gBaP/K8cwBaAZjXH3zmyD3WpN20HlEJpzG1Mn8gixh9Zu4rL4aEaofFZiC4fjN2iVfMW8EsZw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f0f0f0;
      margin: 0;
      padding: 0;
    }

    .portal-container {
      width: 80%;
      margin: 50px auto;
      background-color: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    header h1 {
      margin: 0;
    }

    nav a {
      margin-left: 20px;
      text-decoration: none;
      color: #007bff;
    }

    nav a:hover {
      text-decoration: underline;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    table th,
    table td {
      padding: 10px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    button {
      padding: 10px;
      background-color: #333;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin: 5px 0;
    }

    button:hover {
      background-color: #555;
    }

    .edit-input {
      display: none;
      padding: 10px;
      background-color: #f9f9f9;
      border: 1px solid #ccc;
      border-radius: 4px;
      margin-bottom: 10px;
    }

    .edit-input input {
      width: 100%;
      padding: 8px;
      margin-bottom: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
      font-size: 14px;
    }

    .edit-input button {
      padding: 10px;
      background-color: #333;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    .edit-input button:hover {
      background-color: #555;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 1;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgb(0, 0, 0);
      background-color: rgba(0, 0, 0, 0.4);
      padding-top: 60px;
    }

    .modal-content {
      background-color: #fefefe;
      margin: 5% auto;
      padding: 20px;
      border: 1px solid #888;
      width: 30%;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
    }

    .close:hover,
    .close:focus {
      color: black;
      text-decoration: none;
      cursor: pointer;
    }

    .modal-body {
      padding: 10px;
    }

    .modal-body label {
      display: flex;
      align-items: center;
      margin-bottom: 5px;
    }

    .modal-body input {
      width: calc(100% - 30px);
      padding: 10px;
      margin-bottom: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    .modal-footer {
      text-align: right;
      margin-top: 10px;
    }

    .modal-footer button {
      padding: 10px;
      background-color: #333;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin-left: 10px;
    }

    .modal-footer button:hover {
      background-color: #555;
    }

    .icon {
      margin-right: 10px;
      font-size: 18px;
    }
  </style>
</head>
<body>
  <div class="portal-container">
    <header>
      <h1 style="color:red;">tailwebs.</h1>
      <nav>
        <a href="#">Home</a>
        <a href="logout.php">Logout</a>
      </nav>
    </header>
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Subject</th>
          <th>Mark</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $student) : ?>
          <tr>
            <td><?php echo htmlspecialchars($student['name']); ?></td>
            <td><?php echo htmlspecialchars($student['subject']); ?></td>
            <td><?php echo htmlspecialchars($student['mark']); ?></td>
            <td>
              <form method="post" style="display: inline;">
                <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                <button type="button" onclick="editStudent(this)">Edit</button>
              </form>
              <form method="post" style="display: inline;">
                <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                <button type="submit" name="delete" onclick="return confirm('Are you sure you want to delete this student?')">Delete</button>
              </form>
              <form method="post" class="edit-input" id="edit-form-<?php echo $student['id']; ?>">
                <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                <label for="name-<?php echo $student['id']; ?>"><i class="icon fas fa-user"></i>Name:</label><br>
                <input type="text" id="name-<?php echo $student['id']; ?>" name="name" value="<?php echo htmlspecialchars($student['name']); ?>"><br>
                <label for="subject-<?php echo $student['id']; ?>"><i class="icon fas fa-book"></i>Subject:</label><br>
                <input type="text" id="subject-<?php echo $student['id']; ?>" name="subject" value="<?php echo htmlspecialchars($student['subject']); ?>"><br>
                <label for="mark-<?php echo $student['id']; ?>"><i class="icon fas fa-star"></i>Mark:</label><br>
                <input type="number" id="mark-<?php echo $student['id']; ?>" name="mark" value="<?php echo htmlspecialchars($student['mark']); ?>"><br>
                <button type="submit" name="edit">Save</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <button onclick="openModal()">Add</button>
  </div>

  <!-- Modal -->
  <div id="myModal" class="modal">
    <!-- Modal content -->
    <div class="modal-content">
      <span class="close" onclick="closeModal()">&times;</span>
      <div class="modal-body">
        <form method="post">
          <label for="new-name"><i class="icon fas fa-user"></i>Name:</label><br>
          <input type="text" id="new-name" name="new-name" required><br>
          <label for="new-subject"><i class="icon fas fa-book"></i>Subject:</label><br>
          <input type="text" id="new-subject" name="new-subject" required><br>
          <label for="new-mark"><i class="icon fas fa-star"></i>Mark:</label><br>
          <input type="number" id="new-mark" name="new-mark" required><br>
          <div class="modal-footer">
            <button type="submit" name="add">Add Student</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js" integrity="sha512-ZX67S7Am+YQZP5iC7C/vOECbF/8iZn7ro3aXmBJXOhxDusI/6yMdtK8LmEdOgFtP8ZwnWdQcRdzyChZx6MeqGw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script>
    function editStudent(button) {
      var tr = button.closest('tr');
      tr.querySelector('.edit-input').style.display = 'block';
      tr.querySelector('form:nth-of-type(2)').style.display = 'none';
    }

    function openModal() {
      var modal = document.getElementById('myModal');
      modal.style.display = "block";
    }

    function closeModal() {
      var modal = document.getElementById('myModal');
      modal.style.display = "none";
    }
  </script>
</body>
</html>
