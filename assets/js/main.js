// Mobile menu toggle
document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu?.classList.toggle('hidden');
});

// Task status change handler
function updateTaskStatus(taskId, status) {
    const row = document.querySelector(`tr[data-task-id="${taskId}"]`);
    if (!row) return;

    // Remove existing status classes
    row.classList.remove('task-pending', 'task-problems', 'task-completed'); // Corrected class name
    
    // Add new status class
    row.classList.add(`task-${status.toLowerCase()}`);

    // Show/hide archive button
    const archiveButton = row.querySelector('.archive-button');
    if (archiveButton) {
        archiveButton.style.display = status === 'completed' ? 'inline-flex' : 'none'; //Corrected status
    }

    // Update status in database
    fetch('/bolt/pages/tasks/update_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `task_id=${taskId}&status=${status}`
    });
}

// Google Maps embed handler
function showMap(mapUrl, elementId) {
    const container = document.getElementById(elementId);
    if (!container) return;

    // Extract coordinates from Google Maps URL
    const coords = mapUrl.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
    if (!coords) return;

    const iframe = document.createElement('iframe');
    iframe.src = `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3000!2d${coords[2]}!3d${coords[1]}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zM!5e0!3m2!1sen!2s!4v1600000000000!5m2!1sen!2s`;
    iframe.width = '100%';
    iframe.height = '300';
    iframe.style.border = '0';
    iframe.allowFullscreen = true;
    
    container.innerHTML = '';
    container.appendChild(iframe);
}

// Subtask completion handler
function updateSubtaskCompletion(taskId) {
    const row = document.querySelector(`tr[data-task-id="${taskId}"]`);
    if (!row) return;

    const subtasks = row.querySelectorAll('.subtask-checkbox');
    const total = subtasks.length;
    const completed = Array.from(subtasks).filter(checkbox => checkbox.checked).length;

    // Update progress
    const progressElement = row.querySelector('.task-progress');
    if (progressElement) {
        progressElement.textContent = `${completed}/${total}`;
    }

    // Auto-update status if all subtasks are completed
    if (completed === total) {
        const statusSelect = row.querySelector('.status-select');
        if (statusSelect && statusSelect.value !== 'completed') {
            statusSelect.value = 'completed';
            updateTaskStatus(taskId, 'completed');
        }
    } else {
        const statusSelect = row.querySelector('.status-select');
        if (statusSelect && statusSelect.value === 'completed') {
            statusSelect.value = 'pending';
            updateTaskStatus(taskId, 'pending');
        }
    }
}
