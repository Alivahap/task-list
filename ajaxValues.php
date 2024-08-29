<?php
session_start();
// A file for storing tokens
define('TOKEN_FILE', 'tokens.json');
// Tokens update function
function updateTokens() {
  // If the token is expired or incorrect it updates the token.
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.baubuddy.de/index.php/login",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\"username\":\"365\", \"password\":\"1\"}",
        CURLOPT_HTTPHEADER => [
            "Authorization: Basic QVBJX0V4cGxvcmVyOjEyMzQ1NmlzQUxhbWVQYXNz",
            "Content-Type: application/json"
        ],
    ]);
     //if there are curl error in your localhost. it disabled secure connect.
     //this code optional and its not recomended.I wrote it just in case
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    //Error cases it's return false
    if ($err) {
        return false;
    }
    // Write tokens to file
    $data = json_decode($response, true);
    if (isset($data['oauth']['access_token']) && isset($data['oauth']['refresh_token'])) {
        file_put_contents(TOKEN_FILE, json_encode($data));
        return $data;
    } else {
        echo "Failed to get tokens. Check file permissions.";
        return false;
    }
}

// Get Access tokens from file
function getAccessToken() {
    if (file_exists(TOKEN_FILE)) {
        $data = json_decode(file_get_contents(TOKEN_FILE), true);
        $lastUpdate = isset($data['last_update']) ? $data['last_update'] : 0;
        $currentTime = time();
        // Renew token if it has expired
        if ($currentTime - $lastUpdate > 3600) {
            $data = updateTokens();
            if ($data) {
                $data['last_update'] = $currentTime;
                file_put_contents(TOKEN_FILE, json_encode($data));
            }
        }
        return $data['oauth']['access_token'] ?? null;
    } else {
        $data = updateTokens();
        if ($data) {
            $data['last_update'] = time();
            file_put_contents(TOKEN_FILE, json_encode($data));
            return $data['oauth']['access_token'];
        }
        return null;
    }
}

// API request
function makeApiRequest($url) {
    $apiToken = getAccessToken();
    if (!$apiToken) {
        die("Access Token could not be obtained");
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiToken",
        "Content-Type: application/json"
    ]);
     //if there are curl error in your localhost. it disabled secure connect.
     //this code optional and its not recomended.I wrote it just in case
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
// Check the response and convert it to JSON format
if ($response === false) {
    echo "An error occurred while retrieving data from the API.";
   
} else {
    $data = json_decode($response, true);
    //wrong acces key case. this code update to Access Token
    if(isset($data['error']['code']) ){
            $data = updateTokens();

            $apiToken = getAccessToken();
            if (!$apiToken) {
                die("Access token alınamadı.");
            }
        
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $apiToken",
                "Content-Type: application/json"
            ]);
             //if there are curl error in your localhost. it disabled secure connect.
            //this code optional and its not recomended.I wrote it just in case
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
            $data = json_decode($response, true); 
            return $response;
    }else{
        return $response;
    }
        
}
   
}
$apiUrl = "https://api.baubuddy.de/dev/index.php/v1/tasks/select";
$apiResponse= makeApiRequest($apiUrl);
$tasksArray = json_decode($apiResponse, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Task Search Form</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 600px;
      margin: 50px auto;
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    h1 {
      text-align: center;
    }

    #searchInput {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    #taskList {
      list-style-type: none;
      padding: 0;
    }

    .task-item {
      background-color: #1df70e;
      padding: 15px;
      margin: 10px 0;
      border-radius: 4px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .task-item span {
      font-weight: bold;
    }

    .task-item .description {
      color: #666;
      font-size: 14px;
    }

    .hidden {
      display: none;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Task List</h1>
    <input type="text" id="searchInput" placeholder="Search tasks..." onkeyup="searchTasks()">
    <ul id="taskList">
      <!-- Task items will be displayed here -->
    </ul>
  </div>
  <script>

// Pass API data from PHP to tasks variable
let tasks = <?php echo json_encode($tasksArray); ?>;
// Function to display tasks
function displayTasks() {
  const taskList = document.getElementById('taskList');
  taskList.innerHTML = ''; // Clear previous results
  tasks.forEach(task => {
    const taskItem = document.createElement('li');
    taskItem.className = 'task-item';
    taskItem.style.backgroundColor = task.colorCode;
    taskItem.innerHTML = `
      <span>${task.title}</span>
      <div class="description">${task.description}</div>
    `;
    taskList.appendChild(taskItem);
  });
}

// Function to search tasks
function searchTasks() {
  const searchInput = document.getElementById('searchInput').value.toLowerCase();
  const taskList = document.getElementById('taskList');
  taskList.innerHTML = ''; // Clear previous results
  tasks
    .filter(task => task.title.toLowerCase().includes(searchInput) || task.description.toLowerCase().includes(searchInput))
    .forEach(task => {
      const taskItem = document.createElement('li');
      taskItem.className = 'task-item';
      taskItem.style.backgroundColor = task.colorCode;
      taskItem.innerHTML = `
        <span>${task.title}</span>
        <div class="description">${task.description}</div>
      `;
      taskList.appendChild(taskItem);
    });
}

// Function to fetch new data from the API and update the task list
function fetchData() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '', true); 
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function() {
        if (xhr.status === 200) {
            const newTasks = JSON.parse(xhr.responseText);
            tasks = newTasks;
            displayTasks(); 
        } else {
            console.log('Veri alınamadı.');
        }
    };
    xhr.send('action=fetch_data');
}
window.onload = function() {
    fetchData();
};
setInterval(fetchData, 360000); // 1 hour 360000 milliseconds
// Initial display of tasks
displayTasks();
  </script>
</body>
</html>
