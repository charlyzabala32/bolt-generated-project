<?php
session_start();
require_once '../../config/database.php';

// Obtener el filtro de fecha
$filter = $_GET['filter'] ?? 'all';
$whereClause = '';

switch ($filter) {
    case 'today':
        $whereClause = "WHERE t.schedule_date = CURDATE()";
        break;
    case 'tomorrow':
        $whereClause = "WHERE t.schedule_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
        break;
    case 'this_week':
        $whereClause = "WHERE YEARWEEK(t.schedule_date, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'last_week':
        $whereClause = "WHERE YEARWEEK(t.schedule_date, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)";
        break;
    case 'this_month':
        $whereClause = "WHERE MONTH(t.schedule_date) = MONTH(CURDATE()) AND YEAR(t.schedule_date) = YEAR(CURDATE())";
        break;
}

// Obtener tareas con información del cliente y subtareas
$query = "
    SELECT t.*, c.name as client_name, c.address, c.maps_url,
           GROUP_CONCAT(CONCAT(s.id, ':', s.description, ':', s.completed) SEPARATOR '||') as subtasks_info,
           (SELECT COUNT(*) FROM subtasks WHERE task_id = t.id) as total_subtasks,
           (SELECT COUNT(*) FROM subtasks WHERE task_id = t.id AND completed = 1) as completed_subtasks
    FROM tasks t
    JOIN clients c ON t.client_id = c.id
    LEFT JOIN subtasks s ON t.id = s.task_id
    {$whereClause}
    WHERE t.archived_at IS NULL
    GROUP BY t.id
    ORDER BY t.schedule_date ASC, t.schedule_time ASC
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
    <title>Mantenimientos - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="/bolt/assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col space-y-4 md:space-y-0 md:flex-row md:justify-between md:items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Mantenimientos</h1>
            
            <!-- Filtros de fecha -->
            <div class="flex flex-wrap gap-2">
                <a href="?filter=all" 
                   class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                    </svg>
                    Todas
                </a>
                <a href="?filter=today" 
                   class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 <?php echo $filter === 'today' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                    </svg>
                    Hoy
                </a>
                <a href="?filter=tomorrow" 
                   class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 <?php echo $filter === 'tomorrow' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                    </svg>
                    Mañana
                </a>
                <a href="?filter=this_week" 
                   class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 <?php echo $filter === 'this_week' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                    </svg>
                    Esta semana
                </a>
                <a href="?filter=this_month" 
                   class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 <?php echo $filter === 'this_month' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                    </svg>
                    Este mes
                </a>
                <a href="/bolt/pages/tasks/add.php" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Agregar Tarea
                </a>
            </div>
        </div>
        
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha/Hora</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ubicación</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtareas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gastos</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($tasks as $task): ?>
                        <tr data-task-id="<?php echo $task['id']; ?>" class="task-<?php echo strtolower($task['status']); ?> hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($task['client_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($task['schedule_date'])); ?></div>
                                <div class="text-sm text-gray-500"><?php echo date('H:i', strtotime($task['schedule_time'])); ?></div>
                            </td>
                            <td class="px-6 py-4" style="min-width: 200px;">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($task['description']); ?></div>
                            </td>
                            <td class="px-6 py-4" style="min-width: 200px;" >
		                                  <?php if ($task['maps_url']): ?>
                                <button onclick="toggleMap('map-client-<?php echo $task['id']; ?>', '<?php echo htmlspecialchars($task['maps_url']); ?>')"
                                        class="btn-map inline-flex items-center" style="min-width: 200px;">
                                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                    </svg>
                                    Ver mapa
                                </button>
                                <div id="map-client-<?php echo $task['id']; ?>" class="hidden mt-2 rounded-lg overflow-hidden shadow-lg"></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-2">
                                    <?php
                                    if ($task['subtasks_info']) {
                                        $subtasks = explode('||', $task['subtasks_info']);
                                        foreach ($subtasks as $subtask) {
                                            list($subtaskId, $description, $completed) = explode(':', $subtask);
                                            ?>
                                            <div class="flex items-center space-x-2">
                                                <input type="checkbox" 
                                                       id="subtask-<?php echo $subtaskId; ?>" 
                                                       class="subtask-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition duration-150"
                                                       data-task-id="<?php echo $task['id']; ?>"
                                                       data-subtask-id="<?php echo $subtaskId; ?>"
                                                       <?php echo $completed ? 'checked' : ''; ?>>
                                                <label for="subtask-<?php echo $subtaskId; ?>" class="text-sm text-gray-700">
                                                    <?php echo htmlspecialchars($description); ?>
                                                </label>
                                            </div>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="mt-2 text-sm text-gray-500">
                                    <span class="task-progress"><?php echo $task['completed_subtasks']; ?>/<?php echo $task['total_subtasks']; ?></span> completadas
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <select onchange="updateTaskStatus(<?php echo $task['id']; ?>, this.value)" 
                                        class="status-select form-input rounded-lg text-sm border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition duration-200">
                                    <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="problems" <?php echo $task['status'] === 'problems' ? 'selected' : ''; ?>>Problemas</option>
                                    <option value="terminado" <?php echo $task['status'] === 'terminado' ? 'selected' : ''; ?>>Terminado</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">$<?php echo number_format($task['value'], 2); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">$<?php echo number_format($task['expenses'], 2); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">$<?php echo number_format($task['value'] - $task['expenses'], 2); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex flex-col space-y-2">
                                    <a href="/bolt/pages/tasks/edit.php?id=<?php echo $task['id']; ?>" 
                                       class="inline-flex items-center text-indigo-600 hover:text-indigo-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                        Editar
                                    </a>
                                    <button onclick="deleteTask(<?php echo $task['id']; ?>)" 
                                            class="inline-flex items-center text-red-600 hover:text-red-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        Eliminar
                                    </button>
                                    <button onclick="archiveTask(<?php echo $task['id']; ?>)" 
                                            class="archive-button inline-flex items-center text-yellow-600 hover:text-yellow-900"
                                            style="display: <?php echo $task['status'] === 'terminado' ? 'inline-flex' : 'none'; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z" />
                                            <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        Archivar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50">
                            <td colspan="8" class="px-6 py-4 text-right text-sm font-medium text-gray-900">Total General:</td>
                            <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
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
    function deleteTask(id) {
        if (confirm('¿Está seguro de que desea eliminar esta tarea?')) {
            fetch(`/bolt/pages/tasks/delete.php?id=${id}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error al eliminar la tarea');
                }
            });
        }
    }

    function archiveTask(id) {
        if (confirm('¿Está seguro de que desea archivar esta tarea?')) {
            fetch(`/bolt/pages/tasks/archive.php?id=${id}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error al archivar la tarea');
                }
            });
        }
    }

    // Actualizar estado de subtareas
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.subtask-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const taskId = this.dataset.taskId;
                const subtaskId = this.dataset.subtaskId;
                const completed = this.checked;

                // Actualizar el estado de la subtarea en la base de datos
                fetch('/bolt/pages/tasks/update_subtask.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `task_id=${taskId}&subtask_id=${subtaskId}&completed=${completed ? 1 : 0}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar el progreso visual
                        const row = document.querySelector(`tr[data-task-id="${taskId}"]`);
                        const progressElement = row.querySelector('.task-progress');
                        progressElement.textContent = `${data.completed_subtasks}/${data.total_subtasks}`;

                        // Actualizar el estado de la tarea si es necesario
                        if (data.should_update_status) {
                            const statusSelect = row.querySelector('.status-select');
                            statusSelect.value = data.completed_subtasks === data.total_subtasks ? 'terminado' : 'pending';
                            updateTaskStatus(taskId, statusSelect.value);
                        }
                    }
                });
            });
        });
    });
        function toggleMap(mapElementId, mapUrl) {
        const container = document.getElementById(mapElementId);
        if (!container) return;

        if (container.classList.contains('hidden')) {
            container.classList.remove('hidden');
            if (!container.querySelector('iframe')) {
                embedMap(mapUrl, container);
            }
        } else {
            container.classList.add('hidden');
        }
    }

    function embedMap(mapUrl, container) {
        const iframe = document.createElement('iframe');
        iframe.src = mapUrl;
        iframe.width = '100%';
        iframe.height = '300';
        iframe.style.border = '0';
        iframe.allowFullscreen = true;

        container.innerHTML = '';
        container.appendChild(iframe);
    }
    </script>
</body>
</html>
