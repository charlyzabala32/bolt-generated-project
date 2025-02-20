<?php
session_start();
require_once '../../config/database.php';

// Filtrar por cliente si se proporciona un ID
$clientFilter = isset($_GET['client_id']) ? "AND t.client_id = " . intval($_GET['client_id']) : "";

// Obtener tareas archivadas
$query = "
    SELECT t.*, c.name as client_name, c.address, c.maps_url,
           (SELECT COUNT(*) FROM subtasks WHERE task_id = t.id) as total_subtasks,
           (SELECT COUNT(*) FROM subtasks WHERE task_id = t.id AND completed = 1) as completed_subtasks
    FROM tasks t
    JOIN clients c ON t.client_id = c.id
    WHERE t.archived_at IS NOT NULL {$clientFilter}
    ORDER BY t.archived_at DESC
";

$tasks = $pdo->query($query)->fetchAll();

// Calcular total
$total = 0;
foreach ($tasks as $task) {
    $total += $task['value'] - $task['expenses'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tareas Archivadas - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="/bolt/assets/css/styles.css" rel="stylesheet">
    <style>
        .photo-upload-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        .photo-thumbnail {
            width: 3rem;
            height: 3rem;
            object-fit: cover;
            border-radius: 0.375rem;
            margin-bottom: 0.25rem;
            cursor: pointer;
        }
        .photo-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }
        /* Styles for the image modal */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            overflow: auto;
        }
        .image-modal-content {
            position: relative;
            margin: auto;
            max-width: 90%;
            max-height: 90%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .image-modal-content img {
            max-width: 100%;
            max-height: 90vh;
            display: block;
        }
        .image-modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #f1f1f1;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
    <div class="flex flex-col space-y-4 md:space-y-0 md:flex-row md:justify-between md:items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Mantenimientos Archivados</h1>
        </div>

        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-cell-border">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-cell-border">Fecha/Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-cell-border">Descripción</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-cell-border">Ubicación</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-cell-border">Subtareas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-cell-border">Valor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-cell-border">Gastos</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-cell-border">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-cell-border">Archivado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-cell-border">Antes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Después</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="11" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    No hay tareas archivadas para mostrar.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): ?>
                            <tr data-task-id="<?php echo $task['id']; ?>" class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap table-cell-border">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($task['client_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap table-cell-border">
                                    <div class="text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($task['schedule_date'])); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo date('H:i', strtotime($task['schedule_time'])); ?></div>
                                </td>
                                <td class="px-6 py-4 table-cell-border">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($task['description']); ?></div>
                                </td>
                                <td class="px-6 py-4 table-cell-border" style="min-width: 140px;">
                                                                  <?php if ($task['maps_url']): ?>
                                  <button onclick="openMapModal('<?php echo htmlspecialchars($task['maps_url']); ?>')"
                                            class="inline-flex items-center text-blue-600 hover:text-blue-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                        </svg>
                                        Ver mapa
                                    </button>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 table-cell-border">
                                    <div class="text-sm text-gray-500">
                                        <?php echo $task['completed_subtasks']; ?>/<?php echo $task['total_subtasks']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap table-cell-border">
                                    <div class="text-sm text-gray-500">$<?php echo number_format($task['value'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap table-cell-border">
                                    <div class="text-sm text-gray-500">$<?php echo number_format($task['expenses'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap table-cell-border">
                                    <div class="text-sm font-medium text-gray-900">$<?php echo number_format($task['value'] - $task['expenses'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap table-cell-border text-sm text-gray-500">
                                    <?php echo date('d/m/Y H:i', strtotime($task['archived_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap table-cell-border">
                                    <div class="photo-upload-container">
                                        <?php if ($task['before_photo']): ?>
                                            <div class="photo-actions">
                                                 <img src="<?php echo htmlspecialchars($task['before_photo']); ?>" alt="Before Photo" class="h-12 w-12 object-cover rounded mb-1 cursor-pointer" onclick="openModal('<?php echo htmlspecialchars($task['before_photo']); ?>')"/>
                                                <button onclick="deleteTaskPhoto(<?php echo $task['id']; ?>, 'before_photo')" 
                                                        class="text-red-600 hover:text-red-900 text-xs">
                                                    Eliminar
                                                </button>
                                            </div>
                                        <?php else: ?>
                                      Sin Fotos
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap table-cell-border">
                                    <div class="photo-upload-container">
                                        <?php if ($task['after_photo']): ?>
                                            <div class="photo-actions">
                                                <img src="<?php echo htmlspecialchars($task['after_photo']); ?>" alt="After Photo" class="h-12 w-12 object-cover rounded mb-1 cursor-pointer" onclick="openModal('<?php echo htmlspecialchars($task['after_photo']); ?>')"/>
                                                <button onclick="deleteTaskPhoto(<?php echo $task['id']; ?>, 'after_photo')" 
                                                        class="text-red-600 hover:text-red-900 text-xs">
                                                    Eliminar
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            Sin Fotos
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <div id="imageModal" class="image-modal" onclick="closeModal()">
        <span class="image-modal-close" onclick="closeModal()">&times;</span>
        <div class="image-modal-content">
            <img id="modalImage" src="" alt="Full Size Image">
        </div>
   </div>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50">
                            <td colspan="8" class="px-6 py-4 text-right text-sm font-medium text-gray-900">Total General:</td>
                            <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                $<?php echo number_format($total, 2); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="/bolt/assets/js/main.js"></script>
    <script>
        
// Google Maps modal handler
function openMapModal(mapUrl) {
    const overlay = document.createElement('div');
    overlay.id = 'map-overlay';
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50'; // Added z-50

    const modalContent = document.createElement('div');
    modalContent.className = 'bg-white p-4 rounded-lg shadow-lg w-3/4 h-3/4 relative'; // Added relative

    const closeButton = document.createElement('button');
    closeButton.className = 'absolute top-2 right-2 text-gray-600 hover:text-gray-900';
    closeButton.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>`;
    closeButton.onclick = closeMapModal;

    const iframe = document.createElement('iframe');
    iframe.src = mapUrl;
    iframe.width = '100%';
    iframe.height = '100%';
    iframe.style.border = '0';
    iframe.allowFullscreen = true;

    modalContent.appendChild(closeButton);
    modalContent.appendChild(iframe);
    overlay.appendChild(modalContent);
    document.body.appendChild(overlay);
}

function closeMapModal() {
    const overlay = document.getElementById('map-overlay');
    if (overlay) {
        document.body.removeChild(overlay);
    }
}

        function embedMap(mapUrl, container) {
            const iframe = document.createElement('iframe');
            iframe.src = mapUrl;  // Use the URL directly
            iframe.width = '100%';
            iframe.height = '300';
            iframe.style.border = '0';
            iframe.allowFullscreen = true;

            container.innerHTML = '';
            container.appendChild(iframe);
        }

        function deleteBeforePhoto(taskId) {
    if (confirm('¿Está seguro de que desea eliminar la foto de antes?')) {
        deleteTaskPhoto(taskId, 'before_photo');
    }
}

function deleteAfterPhoto(taskId) {
    if (confirm('¿Está seguro de que desea eliminar la foto de después?')) {
        deleteTaskPhoto(taskId, 'after_photo');
    }
}


function deleteTaskPhoto(taskId, photoType) {
    fetch('/bolt/pages/tasks/delete_task_photo.php', { // Create this PHP file next
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `task_id=${taskId}&photo_type=${photoType}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI - remove image and delete button
            const cell = document.querySelector(`tr[data-task-id="${taskId}"] td:nth-child(${photoType === 'before_photo' ? 10 : 11})`); // Target correct column
            cell.innerHTML = 'Sin Fotos'; // Or reset to upload button if you re-implement upload here
        } else {
            alert('Error al eliminar la foto: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de red al intentar eliminar la foto.');
    });
}


document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[type="file"]').forEach(input => {
        const taskId = input.id.split('_').pop();
        const photoType = input.id.split('_')[0];
        const cell = input.closest('td');

        const uploadButton = document.createElement('button');
        uploadButton.textContent = 'Subir archivo';
        uploadButton.className = 'btn-primary btn-upload';
        input.parentNode.insertBefore(uploadButton, input);
        input.style.display = 'none';

        uploadButton.addEventListener('click', () => {
            input.click();
        });

        input.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);
            formData.append('task_id', taskId);
            formData.append('photo_type', photoType);

            fetch('/bolt/pages/tasks/upload_task_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    uploadButton.style.display = 'none';

                    const removeButton = document.createElement('button');
                    removeButton.textContent = 'Eliminar archivo';
                    removeButton.className = 'btn-danger btn-remove';

                    const fileNameDisplay = document.createElement('span');
                    fileNameDisplay.textContent = file.name;
                    fileNameDisplay.className = 'file-name-display';
                    fileNameDisplay.style.marginLeft = '5px';

                    cell.appendChild(removeButton);
                    cell.appendChild(fileNameDisplay);

                    removeButton.addEventListener('click', () => {
                        // Implement remove file functionality here (e.g., AJAX call to delete file)
                        alert('Remove file functionality not yet implemented.'); // Placeholder
                        uploadButton.style.display = 'inline-flex';
                        removeButton.remove();
                        fileNameDisplay.remove();
                        input.value = '';
                    });

                } else {
                    alert('Error al subir archivo: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de red al subir el archivo.');
            });
        });
    });
});
        function openModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modalImg.src = imageSrc;
            modal.style.display = "block";
        }

        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = "none";
        }
    </script>
</body>
</html>
