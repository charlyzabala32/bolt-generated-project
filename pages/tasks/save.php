<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Insertar la tarea principal
        $stmt = $pdo->prepare("
            INSERT INTO tasks (client_id, description, schedule_time, schedule_date, value, expenses)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['client_id'],
            $_POST['description'],
            $_POST['schedule_time'],
            $_POST['schedule_date'],
            $_POST['value'],
            $_POST['expenses']
        ]);
        
        $taskId = $pdo->lastInsertId();

        // Insertar subtareas
        if (isset($_POST['subtasks']) && is_array($_POST['subtasks'])) {
            $stmt = $pdo->prepare("INSERT INTO subtasks (task_id, description) VALUES (?, ?)");
            foreach ($_POST['subtasks'] as $subtask) {
                if (!empty($subtask)) {
                    $stmt->execute([$taskId, $subtask]);
                }
            }
        }

        $pdo->commit();
        header('Location: /bolt/pages/tasks/list.php');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error al guardar la tarea: " . $e->getMessage());
    }
}
?>
