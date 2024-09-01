<?php
require_once __DIR__ . '/src/APIRequestController.php';
// Session and token management
$sessionManager = new SessionManager();
$sessionManager->startSession();
$sessionManager->checkSession();
$tokenManager = new TokenManager();
$apiRequestManager = new ApiRequestManager($tokenManager);
$apiUrl = "https://api.baubuddy.de/dev/index.php/v1/tasks/select";
$apiResponse = $apiRequestManager->makeApiRequest($apiUrl);
$tasksArray = json_decode($apiResponse, true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Task Search Form</title>
  <link rel="stylesheet" href="css/task-list-style.css">
</head>
<body>
  <div class="container">
    <h1>Task List</h1>
    <input type="text" id="searchInput" placeholder="Search tasks..." onkeyup="searchTasks()">
    <button id="openModalBtn">Select Image</button>
    <ul id="taskList">
      <!-- Task items will be displayed here -->
    </ul>
  </div>
  <div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Select an Image</h2>
        <!-- Dosya seçme butonu -->
        <input type="file" id="imageInput" accept="image/*">  
        <!-- Seçilen resmin gösterileceği alan -->
        <br><br>
        <img id="preview" src="" alt="Image Preview" style="max-width: 100%; height: auto;">
    </div>
</div>
</body>
<!--  Pass API data from PHP to tasks variable -->
<script> let tasks = <?php echo json_encode($tasksArray); ?>; </script>
<script src="js/task-list-script.js"></script>
</html>