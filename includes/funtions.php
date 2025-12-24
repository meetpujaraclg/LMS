<?php
// includes/progress_functions.php

function updateStudentMaterialProgress($pdo, $student_id, $material_id, $course_id, $progress_data) {
    try {
        $is_completed = $progress_data['is_completed'] ?? false;
        $progress_percentage = $progress_data['progress_percentage'] ?? 0;
        $time_spent = $progress_data['time_spent'] ?? 0;
        $last_position = $progress_data['last_position'] ?? null;
        
        // Check if progress record exists
        $checkStmt = $pdo->prepare("SELECT id FROM student_material_progress WHERE student_id = ? AND material_id = ?");
        $checkStmt->execute([$student_id, $material_id]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            // Update existing record
            $updateStmt = $pdo->prepare("
                UPDATE student_material_progress 
                SET is_completed = ?, progress_percentage = ?, time_spent = ?, 
                    last_position = ?, updated_at = NOW(),
                    completed_at = CASE WHEN ? = 1 AND completed_at IS NULL THEN NOW() ELSE completed_at END
                WHERE student_id = ? AND material_id = ?
            ");
            $updateStmt->execute([
                $is_completed, 
                $progress_percentage, 
                $time_spent, 
                $last_position,
                $is_completed,
                $student_id, 
                $material_id
            ]);
        } else {
            // Insert new record
            $insertStmt = $pdo->prepare("
                INSERT INTO student_material_progress 
                (student_id, material_id, course_id, is_completed, progress_percentage, time_spent, last_position, completed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, CASE WHEN ? = 1 THEN NOW() ELSE NULL END)
            ");
            $insertStmt->execute([
                $student_id, 
                $material_id, 
                $course_id,
                $is_completed, 
                $progress_percentage, 
                $time_spent, 
                $last_position,
                $is_completed
            ]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Progress update error: " . $e->getMessage());
        return false;
    }
}

function getCourseProgress($pdo, $student_id, $course_id) {
    try {
        // Get total materials count
        $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM course_materials WHERE course_id = ? AND is_published = 1");
        $totalStmt->execute([$course_id]);
        $totalMaterials = $totalStmt->fetchColumn();
        
        if ($totalMaterials === 0) {
            return ['progress' => 0, 'completed' => 0, 'total' => 0];
        }
        
        // Get completed materials count
        $completedStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM student_material_progress smp
            JOIN course_materials cm ON smp.material_id = cm.id
            WHERE smp.student_id = ? AND cm.course_id = ? AND smp.is_completed = 1
        ");
        $completedStmt->execute([$student_id, $course_id]);
        $completedMaterials = $completedStmt->fetchColumn();
        
        $progress = round(($completedMaterials / $totalMaterials) * 100);
        
        return [
            'progress' => $progress,
            'completed' => $completedMaterials,
            'total' => $totalMaterials
        ];
    } catch (PDOException $e) {
        error_log("Progress calculation error: " . $e->getMessage());
        return ['progress' => 0, 'completed' => 0, 'total' => 0];
    }
}
?>