
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


// Pass API data from PHP to tasks variable
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