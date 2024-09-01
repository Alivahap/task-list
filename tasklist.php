<?php
class SessionManager {
    private $sessionDuration = 1800; // 30 minutes

    public function startSession() {
        session_start();
        if (!isset($_SESSION['token'])) {
            header('Location: index.php');
            exit();
        }
    }

    public function checkSession() {
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $this->sessionDuration)) {
            $this->destroySession();
        } else {
            $_SESSION['login_time'] = time();  // Update session time
        }
    }

    public function destroySession() {
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit();
    }
}

class TokenManager {
    private const TOKEN_FILE = 'tokens.json';

    public function __construct() {
        if (file_exists(self::TOKEN_FILE)) {
            chmod(self::TOKEN_FILE, 0600);
        }
    }

    public function updateTokens() {
        $curl = curl_init();
        $data = array(
            "username" => $_SESSION['username'],
            "password" => $_SESSION['password']
        );

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.baubuddy.de/index.php/login",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic QVBJX0V4cGxvcmVyOjEyMzQ1NmlzQUxhbWVQYXNz",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => false, // Optional for local testing
            CURLOPT_SSL_VERIFYHOST => false  // Optional for local testing
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return false;
        }

        $data = json_decode($response, true);
        if (isset($data['oauth']['access_token']) && isset($data['oauth']['refresh_token'])) {
            file_put_contents(self::TOKEN_FILE, json_encode($data));
            return $data;
        } else {
            echo "Failed to get tokens. Check file permissions.";
            return false;
        }
    }

    public function getAccessToken() {
        if (file_exists(self::TOKEN_FILE)) {
            $data = json_decode(file_get_contents(self::TOKEN_FILE), true);
            $lastUpdate = $data['last_update'] ?? 0;
            $currentTime = time();

            if ($currentTime - $lastUpdate > 3600) {
                $data = $this->updateTokens();
                if ($data) {
                    $data['last_update'] = $currentTime;
                    file_put_contents(self::TOKEN_FILE, json_encode($data));
                }
            }

            return $data['oauth']['access_token'] ?? null;
        } else {
            $data = $this->updateTokens();
            if ($data) {
                $data['last_update'] = time();
                file_put_contents(self::TOKEN_FILE, json_encode($data));
                return $data['oauth']['access_token'];
            }
            return null;
        }
    }
}

class ApiRequestManager {
    private $tokenManager;

    public function __construct(TokenManager $tokenManager) {
        $this->tokenManager = $tokenManager;
    }

    public function makeApiRequest($url) {
        $apiToken = $this->tokenManager->getAccessToken();
        if (!$apiToken) {
            die("Access Token could not be obtained");
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $apiToken",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Optional for local testing
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Optional for local testing

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            echo "An error occurred while retrieving data from the API.";
        } else {
            $data = json_decode($response, true);

            if (isset($data['error']['code'])) {
                $this->tokenManager->updateTokens();

                $apiToken = $this->tokenManager->getAccessToken();
                if (!$apiToken) {
                    die("Access token alınamadı.");
                }

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $apiToken",
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Optional for local testing
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Optional for local testing

                $response = curl_exec($ch);
                $err = curl_error($ch);
                curl_close($ch);

                return $response;
            } else {
                return $response;
            }
        }
    }
}

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
    .modal {
            display: none;
            position: fixed;
            z-index: 1;
            padding-top: 60px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
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

        
        #openModalBtn {
            background-color: #4CAF50; 
            border: none;
            color: white;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 12px; 
            transition-duration: 0.4s; 
        }

        #openModalBtn:hover {
            background-color: white; 
            color: #4CAF50; 
            border: 2px solid #4CAF50; 
        }

        #openModalBtn:active {
            box-shadow: 0 5px #666;
            transform: translateY(4px); 
        }
      

  </style>
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


<script>
// Modal açma ve resim seçimi işlemleri fonksiyon içinde
function initializeModal() {
    var modal = document.getElementById("myModal");
    var btn = document.getElementById("openModalBtn");
    var span = document.getElementsByClassName("close")[0];
    var imageInput = document.getElementById("imageInput");
    var preview = document.getElementById("preview");

    // Modalı aç
    btn.onclick = function() {
        modal.style.display = "block";
    }

    // Modalı kapat
    span.onclick = function() {
        modal.style.display = "none";
    }

    // Modal dışına tıklayınca kapatma
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Dosya seçimini yakala ve önizle
    imageInput.addEventListener('change', function(event) {
        var file = event.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
}


</script>

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
    initializeModal();
};
setInterval(fetchData, 360000); // 1 hour 360000 milliseconds
// Initial display of tasks
displayTasks();
  </script>
</body>
</html>