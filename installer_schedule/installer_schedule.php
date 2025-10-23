<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/conn.php';

// Get current month and year
$current_month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get installer filter
$installer_filter = isset($_GET['installer']) ? $_GET['installer'] : '';

// Get completed view filters
$completed_from_date = isset($_GET['completed_from_date']) ? $_GET['completed_from_date'] : '';
$completed_to_date = isset($_GET['completed_to_date']) ? $_GET['completed_to_date'] : '';
$completed_installer_filter = isset($_GET['completed_installer']) ? $_GET['completed_installer'] : '';

// Get all installers for filter dropdown from users table
$installers_query = "SELECT full_name FROM users WHERE role = 'installer' ORDER BY full_name";
$installers_result = mysqli_query($conn, $installers_query);

// Build where clause for regular views
$where_conditions = ["MONTH(schedule_date) = $current_month", "YEAR(schedule_date) = $current_year"];
if ($installer_filter) {
    $where_conditions[] = "installer_name = '$installer_filter'";
}
$where_clause = implode(' AND ', $where_conditions);

// Build where clause for completed view
$completed_where_conditions = ["status = 'Completed'"];
if ($completed_from_date) {
    $completed_where_conditions[] = "DATE(completed_at) >= '$completed_from_date'";
}
if ($completed_to_date) {
    $completed_where_conditions[] = "DATE(completed_at) <= '$completed_to_date'";
}
if ($completed_installer_filter) {
    $completed_where_conditions[] = "installer_name = '$completed_installer_filter'";
}
$completed_where_clause = implode(' AND ', $completed_where_conditions);

// Get schedules for the month
$query = "SELECT * FROM installer_schedules WHERE $where_clause ORDER BY schedule_date, schedule_time";
$result = mysqli_query($conn, $query);

// Get completed schedules for completed view
$completed_query = "SELECT * FROM installer_schedules WHERE $completed_where_clause ORDER BY completed_at DESC";
$completed_result = mysqli_query($conn, $completed_query);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_schedules,
    SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
    FROM installer_schedules WHERE $where_clause";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Group schedules by date
$schedules_by_date = array();
mysqli_data_seek($result, 0);
while ($row = mysqli_fetch_assoc($result)) {
    $date = $row['schedule_date'];
    if (!isset($schedules_by_date[$date])) {
        $schedules_by_date[$date] = array();
    }
    $schedules_by_date[$date][] = $row;
}

// Get month name
$month_name = date('F', mktime(0, 0, 0, $current_month, 1, $current_year));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> Assign Schedule Install</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        #content-wrapper {
            background: transparent;
        }

        .container-fluid {
            padding: 20px;
        }

        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 24px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: 
                0 20px 60px -12px rgba(0, 0, 0, 0.08),
                0 0 0 1px rgba(59, 130, 246, 0.1);
            border: 2px solid rgba(59, 130, 246, 0.1);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 30% 30%, rgba(59, 130, 246, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 70% 70%, rgba(139, 92, 246, 0.05) 0%, transparent 40%);
            animation: float 20s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(20px, 20px) rotate(5deg); }
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 24px;
            position: relative;
            z-index: 2;
        }

        .header-title h1 {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 50%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { filter: brightness(1); }
            50% { filter: brightness(1.2); }
        }

        .header-subtitle {
            color: #64748b;
            font-size: 16px;
            font-weight: 500;
        }

        .view-toggles {
            display: flex;
            gap: 4px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 6px;
            border-radius: 20px;
            box-shadow: 
                0 4px 20px -5px rgba(0, 0, 0, 0.08),
                0 0 0 1px rgba(59, 130, 246, 0.1);
            border: 2px solid rgba(59, 130, 246, 0.1);
            position: relative;
            overflow: hidden;
        }

        .view-toggles::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.02) 0%, rgba(139, 92, 246, 0.02) 100%);
            pointer-events: none;
        }

        .view-btn {
            padding: 12px 24px;
            border: none;
            background: transparent;
            color: #64748b;
            font-weight: 600;
            cursor: pointer;
            border-radius: 16px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 2;
            font-size: 14px;
            min-width: 120px;
            justify-content: center;
        }

        .view-btn i {
            font-size: 16px;
            transition: transform 0.3s ease;
        }

        .view-btn.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 
                0 4px 12px -2px rgba(59, 130, 246, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .view-btn.active i {
            transform: scale(1.1);
        }

        .view-btn:hover:not(.active) {
            background: rgba(59, 130, 246, 0.08);
            color: #3b82f6;
            transform: translateY(-1px);
        }

        .view-btn:hover:not(.active) i {
            transform: scale(1.05);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 
                0 10px 40px -10px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(59, 130, 246, 0.08);
            border: 2px solid rgba(59, 130, 246, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--accent-color);
            border-radius: 0 0 5px 0;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center, rgba(59, 130, 246, 0.02) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                0 25px 60px -10px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .stat-card:hover::after {
            opacity: 1;
        }

        .stat-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 4px 12px -2px rgba(59, 130, 246, 0.3);
            transition: transform 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
        }

        .stat-value {
            font-size: 40px;
            font-weight: 800;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        .stat-label {
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 6px;
        }

        /* Filter Card */
        .filter-card {
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 32px;
            box-shadow: 
                0 10px 40px -10px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(59, 130, 246, 0.08);
            border: 2px solid rgba(59, 130, 246, 0.1);
        }

        .filter-card label {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
            font-size: 14px;
        }

        /* Calendar Container */
        .calendar-container {
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 
                0 20px 60px -12px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(59, 130, 246, 0.08);
            border: 2px solid rgba(59, 130, 246, 0.1);
        }

        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .month-nav {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .nav-btn {
            padding: 12px 20px;
            border: none;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 16px;
            cursor: pointer;
            font-weight: 600;
            color: #64748b;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-block;
            border: 2px solid rgba(59, 130, 246, 0.1);
            box-shadow: 0 2px 8px -2px rgba(0, 0, 0, 0.1);
        }

        .nav-btn:hover {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(59, 130, 246, 0.4);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .month-title {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            min-width: 200px;
            text-align: center;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 12px;
        }

        .day-header {
            text-align: center;
            padding: 16px 12px;
            font-weight: 700;
            color: #2d3748;
            font-size: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calendar-day {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            min-height: 140px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .calendar-day.empty-day {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            cursor: default;
            opacity: 0.3;
        }

        .calendar-day.empty-day:hover {
            transform: none;
            box-shadow: none;
            background: #f8fafc;
        }

        .calendar-day:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            background: #f8fafc;
            border-color: #cbd5e0;
        }

        .day-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        .day-number {
            font-weight: 700;
            color: #2d3748;
            font-size: 18px;
            line-height: 1;
        }

        .day-name {
            font-weight: 500;
            color: #718096;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calendar-day.today {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #5a67d8;
        }

        .calendar-day.today .day-number {
            color: white;
        }

        .calendar-day.today .day-name {
            color: rgba(255, 255, 255, 0.8);
        }

        .calendar-day.today .day-info {
            border-bottom-color: rgba(255, 255, 255, 0.2);
        }

        .calendar-day.today:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6b3f8f 100%);
        }

        .schedule-mini {
            background: white;
            border-radius: 6px;
            padding: 6px 8px;
            font-size: 11px;
            margin-bottom: 4px;
            border-left: 3px solid;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .schedule-mini:hover {
            transform: translateX(2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .schedule-count {
            background: #e2e8f0;
            color: #4a5568;
            border-radius: 12px;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: 600;
            text-align: center;
            margin-top: auto;
            align-self: center;
        }

        .calendar-day.today .schedule-count {
            background: rgba(255,255,255,0.3);
            color: white;
        }

        /* Modern Card Grid Layout */
        .kanban-container {
            display: none;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 24px;
            padding: 24px 0;
        }

        .kanban-column {
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 
                0 10px 40px -10px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(59, 130, 246, 0.08);
            border: 2px solid rgba(59, 130, 246, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .kanban-column::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--accent-color);
            border-radius: 24px 24px 0 0;
        }

        .kanban-column:hover {
            transform: translateY(-8px);
            box-shadow: 
                0 25px 60px -10px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid rgba(226, 232, 240, 0.5);
            position: relative;
        }

        .column-title {
            font-weight: 700;
            font-size: 18px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .column-title i {
            font-size: 20px;
            color: var(--accent-color);
        }

        .column-count {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-radius: 20px;
            padding: 6px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 4px 12px -2px rgba(59, 130, 246, 0.4);
        }

        .kanban-cards {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-height: 200px;
        }

        .kanban-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            padding: 20px;
            cursor: move;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid rgba(59, 130, 246, 0.1);
            box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }

        .kanban-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--accent-color);
            border-radius: 0 0 4px 0;
        }

        .kanban-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.15);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .kanban-card.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }

        .card-title {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
            font-size: 16px;
            line-height: 1.4;
        }

        .card-detail {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .card-detail i {
            color: var(--accent-color);
            width: 16px;
        }

        .installer-badge {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 2px 8px -2px rgba(59, 130, 246, 0.4);
            gap: 4px;
            margin-top: 8px;
        }

        .card-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .action-btn {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        /* Modern List View */
        .list-container {
            display: none;
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 
                0 20px 60px -12px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(59, 130, 246, 0.08);
            border: 2px solid rgba(59, 130, 246, 0.1);
        }

        .list-group {
            margin-bottom: 32px;
        }

        .list-date {
            font-weight: 700;
            color: #1e293b;
            font-size: 20px;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 3px solid rgba(59, 130, 246, 0.2);
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            gap: 8px;
        }

        .list-item {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid rgba(59, 130, 246, 0.1);
            box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
            flex-wrap: wrap;
            gap: 16px;
        }

        .list-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--accent-color);
            border-radius: 0 0 5px 0;
        }

        .list-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.15);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .list-info {
            flex: 1;
            min-width: 300px;
            position: relative;
            z-index: 2;
        }

        .list-customer {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
            font-size: 18px;
            line-height: 1.4;
        }

        .list-details {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .list-detail {
            color: #4a5568;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .view-content > div {
            animation: fadeIn 0.5s ease;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .calendar-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .view-toggles {
                width: 100%;
                padding: 4px;
            }

            .view-btn {
                flex: 1;
                justify-content: center;
                padding: 10px 16px;
                font-size: 13px;
                min-width: auto;
            }

            .view-btn i {
                font-size: 14px;
            }

            .calendar-day {
                min-height: 100px;
            }

            .list-item {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
            }

            .list-info {
                min-width: 100%;
                margin-bottom: 16px;
            }

            .card-actions {
                width: 100%;
            }

            .kanban-container {
                grid-template-columns: 1fr;
                gap: 16px;
                padding: 16px 0;
            }

            .kanban-column {
                min-width: 100%;
                padding: 20px;
            }

            .kanban-card {
                padding: 16px;
            }
        }

        /* Modal Enhancements */
        .modal-content {
            border-radius: 16px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px 16px 0 0;
            padding: 20px 24px;
        }

        .modal-header .close {
            color: white;
            opacity: 0.8;
        }

        .modal-header .close:hover {
            opacity: 1;
        }

        .modal-title {
            font-weight: 600;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 2px solid #e2e8f0;
        }

        /* Form Enhancements */
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-group label {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 8px;
        }

        /* Completion Image Styles */
        .completion-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .completion-thumbnail:hover {
            border-color: #48bb78;
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
        }

        .completion-thumbnail-large {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 12px;
            cursor: pointer;
            border: 3px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .completion-thumbnail-large:hover {
            border-color: #48bb78;
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.3);
        }

        .completion-image-preview {
            text-align: center;
        }

        .completed-card-container {
            margin-bottom: 1rem;
        }

        .completed-card-container .card {
            transition: all 0.3s ease;
        }

        .completed-card-container .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        /* Completion Image Modal */
        .completion-image-modal .modal-dialog {
            max-width: 800px;
        }

        .completion-image-modal .modal-body {
            text-align: center;
            padding: 2rem;
        }

        .completion-image-modal .modal-body img {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include('../includes/topbar.php'); ?>

            <div class="container-fluid">
                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <div class="header-content">
                        <div class="header-title">
                            <h1>Assign Schedule Install</h1>
                            <div class="header-subtitle"><?php echo $month_name . ' ' . $current_year; ?> • Manage schedules and assignments</div>
                        </div>
                        <div class="view-toggles">
                            <button class="view-btn active" data-view="calendar">
                                <i class="fas fa-calendar-alt"></i> Calendar
                            </button>
                            <button class="view-btn" data-view="kanban">
                                <i class="fas fa-tasks"></i> Schedule
                            </button>
                            <button class="view-btn" data-view="list">
                                <i class="fas fa-list"></i> Completed
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card" style="--accent-color: #667eea;">
                        <div class="stat-content">
                            <div>
                                <div class="stat-value"><?php echo $stats['total_schedules']; ?></div>
                                <div class="stat-label">Total Schedules</div>
                            </div>
                            <div class="stat-icon" style="background: #e3e8ff; color: #667eea;">
                                <i class="fas fa-calendar"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" style="--accent-color: #f6ad55;">
                        <div class="stat-content">
                            <div>
                                <div class="stat-value"><?php echo $stats['scheduled']; ?></div>
                                <div class="stat-label">Scheduled</div>
                            </div>
                            <div class="stat-icon" style="background: #feebc8; color: #f6ad55;">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" style="--accent-color: #4299e1;">
                        <div class="stat-content">
                            <div>
                                <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                                <div class="stat-label">In Progress</div>
                            </div>
                            <div class="stat-icon" style="background: #bee3f8; color: #4299e1;">
                                <i class="fas fa-tools"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" style="--accent-color: #48bb78;">
                        <div class="stat-content">
                            <div>
                                <div class="stat-value"><?php echo $stats['completed']; ?></div>
                                <div class="stat-label">Completed</div>
                            </div>
                            <div class="stat-icon" style="background: #c6f6d5; color: #48bb78;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="filter-card">
                    <form method="GET" action="">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label for="installer">Filter by Installer</label>
                                <select class="form-control" id="installer" name="installer">
                                    <option value="">All Installers</option>
                                    <?php
                                    mysqli_data_seek($installers_result, 0);
                                    while ($installer = mysqli_fetch_assoc($installers_result)) {
                                        $selected = ($installer_filter == $installer['full_name']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($installer['full_name']) . "' $selected>" . htmlspecialchars($installer['full_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Apply Filter
                                </button>
                                <a href="installer_dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                        <input type="hidden" name="month" value="<?php echo $current_month; ?>">
                        <input type="hidden" name="year" value="<?php echo $current_year; ?>">
                    </form>
                </div>

                <!-- Completed Filter Card -->
                <div class="filter-card" id="completed-filter-card" style="display: none;">
                    <form method="GET" action="">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label for="completed_from_date">From Date</label>
                                <input type="date" class="form-control" id="completed_from_date" name="completed_from_date" value="<?php echo isset($_GET['completed_from_date']) ? $_GET['completed_from_date'] : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="completed_to_date">To Date</label>
                                <input type="date" class="form-control" id="completed_to_date" name="completed_to_date" value="<?php echo isset($_GET['completed_to_date']) ? $_GET['completed_to_date'] : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="completed_installer">Filter by Installer</label>
                                <select class="form-control" id="completed_installer" name="completed_installer">
                                    <option value="">All Installers</option>
                                    <?php
                                    mysqli_data_seek($installers_result, 0);
                                    while ($installer = mysqli_fetch_assoc($installers_result)) {
                                        $selected = (isset($_GET['completed_installer']) && $_GET['completed_installer'] == $installer['full_name']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($installer['full_name']) . "' $selected>" . htmlspecialchars($installer['full_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Apply Filter
                                </button>
                                <a href="installer_schedule.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                        <input type="hidden" name="month" value="<?php echo $current_month; ?>">
                        <input type="hidden" name="year" value="<?php echo $current_year; ?>">
                    </form>
                </div>

                <!-- View Content Container -->
                <div class="view-content">
                    <!-- Calendar View -->
                    <div id="calendar-view" class="calendar-container">
                        <div class="calendar-nav">
                            <div class="month-nav">
                                <a href="?month=<?php echo $current_month - 1; ?>&year=<?php echo $current_year; ?>&installer=<?php echo urlencode($installer_filter); ?>" class="nav-btn">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                                <div class="month-title"><?php echo $month_name . ' ' . $current_year; ?></div>
                                <a href="?month=<?php echo $current_month + 1; ?>&year=<?php echo $current_year; ?>&installer=<?php echo urlencode($installer_filter); ?>" class="nav-btn">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                            <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>&installer=<?php echo urlencode($installer_filter); ?>" class="nav-btn">
                                <i class="fas fa-calendar-day"></i> Today
                            </a>
                        </div>

                        <div class="calendar-grid">
                            <div class="day-header">Sunday</div>
                            <div class="day-header">Monday</div>
                            <div class="day-header">Tuesday</div>
                            <div class="day-header">Wednesday</div>
                            <div class="day-header">Thursday</div>
                            <div class="day-header">Friday</div>
                            <div class="day-header">Saturday</div>

                            <?php
                            $days_in_month = date('t', mktime(0, 0, 0, $current_month, 1, $current_year));
                            $first_day_of_month = date('w', mktime(0, 0, 0, $current_month, 1, $current_year)); // 0 = Sunday, 1 = Monday, etc.
                            
                            // Add empty cells for days before the first day of the month
                            for ($i = 0; $i < $first_day_of_month; $i++) {
                                echo '<div class="calendar-day empty-day"></div>';
                            }
                            
                            // Add days of the month
                            for ($day = 1; $day <= $days_in_month; $day++) {
                                $current_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                                $has_schedules = isset($schedules_by_date[$current_date]);
                                $schedules_count = $has_schedules ? count($schedules_by_date[$current_date]) : 0;
                                $is_today = ($current_date == date('Y-m-d'));
                                $day_name = date('l', strtotime($current_date)); // Full day name
                                $day_abbr = date('D', strtotime($current_date)); // Abbreviated day name
                                
                                echo '<div class="calendar-day ' . ($is_today ? 'today' : '') . '" data-date="' . $current_date . '">';
                                echo '<div class="day-info">';
                                echo '<div class="day-number">' . $day . '</div>';
                                echo '<div class="day-name">' . $day_abbr . '</div>';
                                echo '</div>';
                                
                                if ($has_schedules) {
                                    $display_count = min(3, $schedules_count);
                                    for ($i = 0; $i < $display_count; $i++) {
                                        $schedule = $schedules_by_date[$current_date][$i];
                                        $color = '#cbd5e0';
                                        if ($schedule['status'] == 'Scheduled') $color = '#f6ad55';
                                        elseif ($schedule['status'] == 'In Progress') $color = '#4299e1';
                                        elseif ($schedule['status'] == 'Completed') $color = '#48bb78';
                                        
                                        $name = strlen($schedule['customer_name']) > 15 ? substr($schedule['customer_name'], 0, 15) . '...' : $schedule['customer_name'];
                                        echo '<div class="schedule-mini" style="border-color: ' . $color . ';" title="' . htmlspecialchars($schedule['customer_name']) . '">' . htmlspecialchars($name) . '</div>';
                                    }
                                    if ($schedules_count > 3) {
                                        echo '<div class="schedule-count">' . $schedules_count . '</div>';
                                    }
                                }
                                
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Kanban View -->
                    <div id="kanban-view" class="kanban-container">
                        <!-- Unassigned Column -->
                        <div class="kanban-column">
                            <div class="column-header">
                                <div class="column-title">
                                    <i class="fas fa-inbox"></i> Unassigned
                                </div>
                                <div class="column-count">
                                    <?php
                                    $unassigned_count_query = "SELECT COUNT(*) as count FROM installer_schedules WHERE (installer_name = '' OR installer_name IS NULL) AND $where_clause";
                                    $unassigned_count_result = mysqli_query($conn, $unassigned_count_query);
                                    $unassigned_count = mysqli_fetch_assoc($unassigned_count_result)['count'];
                                    echo $unassigned_count;
                                    ?>
                                </div>
                            </div>
                            <div class="kanban-cards">
                                <?php
                                $unassigned_query = "SELECT * FROM installer_schedules WHERE (installer_name = '' OR installer_name IS NULL) AND $where_clause ORDER BY schedule_date, schedule_time";
                                $unassigned_result = mysqli_query($conn, $unassigned_query);
                                
                                if (mysqli_num_rows($unassigned_result) > 0) {
                                    while ($schedule = mysqli_fetch_assoc($unassigned_result)) {
                                        echo '<div class="kanban-card" style="border-color: #cbd5e0;" data-schedule-id="' . $schedule['id'] . '">';
                                        echo '<div class="card-title">' . htmlspecialchars($schedule['customer_name']) . '</div>';
                                        echo '<div class="card-detail"><i class="fas fa-calendar"></i> ' . date('M d, Y', strtotime($schedule['schedule_date'])) . '</div>';
                                        echo '<div class="card-detail"><i class="fas fa-clock"></i> ' . date('h:i A', strtotime($schedule['schedule_time'])) . '</div>';
                                        echo '<div class="card-detail"><i class="fas fa-cog"></i> ' . htmlspecialchars($schedule['service_type']) . '</div>';
                                        echo '<div class="card-actions">';
                                        echo '<button class="action-btn btn-primary edit-schedule-btn" data-schedule-id="' . $schedule['id'] . '">Assign</button>';
                                        echo '<button class="action-btn btn-secondary edit-schedule-btn" data-schedule-id="' . $schedule['id'] . '">Edit</button>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<div class="empty-state"><i class="fas fa-check-circle"></i><p>No unassigned schedules</p></div>';
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Scheduled Column -->
                        <div class="kanban-column">
                            <div class="column-header">
                                <div class="column-title">
                                    <i class="fas fa-clock"></i> Scheduled
                                </div>
                                <div class="column-count"><?php echo $stats['scheduled']; ?></div>
                            </div>
                            <div class="kanban-cards">
                                <?php
                                mysqli_data_seek($result, 0);
                                $scheduled_count = 0;
                                while ($schedule = mysqli_fetch_assoc($result)) {
                                    if ($schedule['status'] == 'Scheduled' && !empty($schedule['installer_name'])) {
                                        $scheduled_count++;
                                        echo '<div class="kanban-card" style="border-color: #f6ad55;" data-schedule-id="' . $schedule['id'] . '">';
                                        echo '<div class="card-title">' . htmlspecialchars($schedule['customer_name']) . '</div>';
                                        echo '<div class="card-detail"><i class="fas fa-calendar"></i> ' . date('M d, Y', strtotime($schedule['schedule_date'])) . '</div>';
                                        echo '<div class="card-detail"><i class="fas fa-clock"></i> ' . date('h:i A', strtotime($schedule['schedule_time'])) . '</div>';
                                        echo '<div class="card-detail"><i class="fas fa-cog"></i> ' . htmlspecialchars($schedule['service_type']) . '</div>';
                                        echo '<div class="installer-badge"><i class="fas fa-user"></i> ' . htmlspecialchars($schedule['installer_name']) . '</div>';
                                        echo '<div class="card-actions">';
                                        echo '<button class="action-btn btn-primary">Start</button>';
                                        echo '<button class="action-btn btn-secondary edit-schedule-btn" data-schedule-id="' . $schedule['id'] . '">Edit</button>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                }
                                if ($scheduled_count == 0) {
                                    echo '<div class="empty-state"><i class="fas fa-clock"></i><p>No scheduled tasks</p></div>';
                                }
                                ?>
                            </div>
                        </div>

                        <!-- In Progress Column -->
                        <div class="kanban-column">
                            <div class="column-header">
                                <div class="column-title">
                                    <i class="fas fa-tools"></i> In Progress
                                </div>
                                <div class="column-count"><?php echo $stats['in_progress']; ?></div>
                            </div>
                            <div class="kanban-cards">
                                <?php
                                mysqli_data_seek($result, 0);
                                $in_progress_count = 0;
                                while ($schedule = mysqli_fetch_assoc($result)) {
                                    if ($schedule['status'] == 'In Progress') {
                                        $in_progress_count++;
                                        echo '<div class="kanban-card" style="border-color: #4299e1;" data-schedule-id="' . $schedule['id'] . '">';
                                        echo '<div class="card-title">' . htmlspecialchars($schedule['customer_name']) . '</div>';
                                        echo '<div class="card-detail"><i class="fas fa-calendar"></i> ' . date('M d, Y', strtotime($schedule['schedule_date'])) . '</div>';
                                        echo '<div class="card-detail"><i class="fas fa-clock"></i> ' . date('h:i A', strtotime($schedule['schedule_time'])) . '</div>';
                                        echo '<div class="card-detail"><i class="fas fa-cog"></i> ' . htmlspecialchars($schedule['service_type']) . '</div>';
                                        echo '<div class="installer-badge"><i class="fas fa-user"></i> ' . htmlspecialchars($schedule['installer_name']) . '</div>';
                                        echo '<div class="card-actions">';
                                        echo '<button class="action-btn btn-primary">Complete</button>';
                                        echo '<button class="action-btn btn-secondary edit-schedule-btn" data-schedule-id="' . $schedule['id'] . '">Edit</button>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                }
                                if ($in_progress_count == 0) {
                                    echo '<div class="empty-state"><i class="fas fa-tools"></i><p>No tasks in progress</p></div>';
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Completed Column -->
                        <div class="kanban-column">
                            <div class="column-header">
                                <div class="column-title">
                                    <i class="fas fa-check-circle"></i> Completed
                                </div>
                                <div class="column-count"><?php echo $stats['completed']; ?></div>
                            </div>
                            <div class="kanban-cards">
                                <?php
                                mysqli_data_seek($result, 0);
                                $completed_count = 0;
                                while ($schedule = mysqli_fetch_assoc($result)) {
                                    if ($schedule['status'] == 'Completed') {
                                        $completed_count++;
                                        echo '<div class="kanban-card completed-card" style="border-color: #48bb78;" data-schedule-id="' . $schedule['id'] . '">';
                                        echo '<div class="card-title">' . htmlspecialchars($schedule['customer_name']) . '</div>';
                                        echo '<div class="card-detail"><i class="fas fa-calendar"></i> ' . date('M d, Y', strtotime($schedule['schedule_date'])) . '</div>';
                                        echo '<div class="card-detail"><i class="fas fa-clock"></i> ' . date('h:i A', strtotime($schedule['schedule_time'])) . '</div>';
                                        echo '<div class="card-detail"><i class="fas fa-cog"></i> ' . htmlspecialchars($schedule['service_type']) . '</div>';
                                        echo '<div class="installer-badge"><i class="fas fa-user"></i> ' . htmlspecialchars($schedule['installer_name']) . '</div>';
                                        
                                        // Show completion image if available
                                        if (!empty($schedule['completion_image'])) {
                                            echo '<div class="completion-image-preview mt-2">';
                                            echo '<img src="../' . htmlspecialchars($schedule['completion_image']) . '" alt="Completion Image" class="completion-thumbnail" onclick="viewCompletionImage(\'' . htmlspecialchars($schedule['completion_image']) . '\')">';
                                            echo '<small class="text-muted d-block mt-1"><i class="fas fa-camera"></i> Completion Photo</small>';
                                            echo '</div>';
                                        }
                                        
                                        // Show employee list if available
                                        if (!empty($schedule['employee_list'])) {
                                            echo '<div class="employee-list-preview mt-2">';
                                            echo '<small class="text-muted"><i class="fas fa-users"></i> Team:</small><br>';
                                            $employees = preg_split('/[,\n\r]+/', $schedule['employee_list']);
                                            foreach($employees as $employee) {
                                                $employee = trim($employee);
                                                if (!empty($employee)) {
                                                    echo '<span class="badge badge-light mr-1 mb-1">' . htmlspecialchars($employee) . '</span>';
                                                }
                                            }
                                            echo '</div>';
                                        }
                                        
                                        // Show completion date if available
                                        if (!empty($schedule['completed_at'])) {
                                            echo '<div class="card-detail mt-2"><i class="fas fa-check-circle text-success"></i> Completed: ' . date('M d, Y g:i A', strtotime($schedule['completed_at'])) . '</div>';
                                        }
                                        
                                        echo '<div class="card-actions">';
                                        echo '<button class="action-btn btn-primary edit-schedule-btn" data-schedule-id="' . $schedule['id'] . '">View Details</button>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                }
                                if ($completed_count == 0) {
                                    echo '<div class="empty-state"><i class="fas fa-check-circle"></i><p>No completed tasks</p></div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- List View -->
                    <div id="list-view" class="list-container">
                        <?php
                        // Check if we're in completed view mode
                        $is_completed_view = isset($_GET['completed_from_date']) || isset($_GET['completed_to_date']) || isset($_GET['completed_installer']);
                        
                        if ($is_completed_view) {
                            // Show only completed installations
                            if (mysqli_num_rows($completed_result) > 0) {
                                echo '<div class="completed-installations-header mb-4">';
                                echo '<h4 class="text-success"><i class="fas fa-check-circle mr-2"></i>Completed Installations</h4>';
                                echo '<p class="text-muted">Showing completed installations with completion photos</p>';
                                echo '</div>';
                                
                                while ($schedule = mysqli_fetch_assoc($completed_result)) {
                                    echo '<div class="completed-card-container mb-3">';
                                    echo '<div class="card shadow-sm" style="border-left: 4px solid #48bb78;">';
                                    echo '<div class="card-body">';
                                    echo '<div class="row">';
                                    echo '<div class="col-md-8">';
                                    echo '<h5 class="card-title text-primary">' . htmlspecialchars($schedule['customer_name']) . '</h5>';
                                    echo '<div class="row">';
                                    echo '<div class="col-md-6">';
                                    echo '<p class="mb-1"><i class="fas fa-clock text-muted"></i> Scheduled: ' . date('M d, Y h:i A', strtotime($schedule['schedule_date'] . ' ' . $schedule['schedule_time'])) . '</p>';
                                    echo '<p class="mb-1"><i class="fas fa-cog text-muted"></i> ' . htmlspecialchars($schedule['service_type']) . '</p>';
                                    echo '<p class="mb-1"><i class="fas fa-user text-muted"></i> ' . htmlspecialchars($schedule['installer_name']) . '</p>';
                                    echo '</div>';
                                    echo '<div class="col-md-6">';
                                    echo '<p class="mb-1"><i class="fas fa-map-marker-alt text-muted"></i> ' . htmlspecialchars(substr($schedule['address'], 0, 50)) . '...</p>';
                                    if (!empty($schedule['completed_at'])) {
                                        echo '<p class="mb-1"><i class="fas fa-check-circle text-success"></i> Completed: ' . date('M d, Y g:i A', strtotime($schedule['completed_at'])) . '</p>';
                                    }
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '<div class="col-md-4 text-right">';
                                    if (!empty($schedule['completion_image'])) {
                                        echo '<img src="../' . htmlspecialchars($schedule['completion_image']) . '" alt="Completion Image" class="completion-thumbnail-large" onclick="viewCompletionImage(\'' . htmlspecialchars($schedule['completion_image']) . '\')">';
                                        echo '<p class="small text-muted mt-1"><i class="fas fa-camera"></i> Completion Photo</p>';
                                    }
                                    
                                    // Show employee list if available
                                    if (!empty($schedule['employee_list'])) {
                                        echo '<div class="employee-list-section mt-2">';
                                        echo '<p class="small text-muted mb-1"><i class="fas fa-users"></i> Installation Team:</p>';
                                        $employees = preg_split('/[,\n\r]+/', $schedule['employee_list']);
                                        foreach($employees as $employee) {
                                            $employee = trim($employee);
                                            if (!empty($employee)) {
                                                echo '<span class="badge badge-secondary mr-1 mb-1">' . htmlspecialchars($employee) . '</span>';
                                            }
                                        }
                                        echo '</div>';
                                    }
                                    
                                    echo '<button class="btn btn-primary btn-sm edit-schedule-btn" data-schedule-id="' . $schedule['id'] . '">View Details</button>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="empty-state">';
                                echo '<i class="fas fa-search"></i>';
                                echo '<h4>No Completed Installations Found</h4>';
                                echo '<p>No completed installations match your filter criteria.</p>';
                                echo '</div>';
                            }
                        } else {
                            // Show regular list view (grouped by date)
                            $grouped_schedules = array();
                            mysqli_data_seek($result, 0);
                            while ($schedule = mysqli_fetch_assoc($result)) {
                                $date = $schedule['schedule_date'];
                                if (!isset($grouped_schedules[$date])) {
                                    $grouped_schedules[$date] = array();
                                }
                                $grouped_schedules[$date][] = $schedule;
                            }
                            
                            ksort($grouped_schedules);
                            
                            if (count($grouped_schedules) > 0) {
                                foreach ($grouped_schedules as $date => $schedules) {
                                    $date_obj = new DateTime($date);
                                    $today = new DateTime();
                                    $tomorrow = new DateTime('+1 day');
                                    
                                    $date_label = '';
                                    if ($date_obj->format('Y-m-d') == $today->format('Y-m-d')) {
                                        $date_label = 'Today - ' . $date_obj->format('F j, Y');
                                    } elseif ($date_obj->format('Y-m-d') == $tomorrow->format('Y-m-d')) {
                                        $date_label = 'Tomorrow - ' . $date_obj->format('F j, Y');
                                    } else {
                                        $date_label = $date_obj->format('F j, Y');
                                    }
                                    
                                    echo '<div class="list-group">';
                                    echo '<div class="list-date"><i class="fas fa-calendar-alt"></i> ' . $date_label . '</div>';
                                    
                                    foreach ($schedules as $schedule) {
                                        $color = '#cbd5e0';
                                        if ($schedule['status'] == 'Scheduled') $color = '#f6ad55';
                                        elseif ($schedule['status'] == 'In Progress') $color = '#4299e1';
                                        elseif ($schedule['status'] == 'Completed') $color = '#48bb78';
                                        
                                        // Show completed installations as cards
                                        if ($schedule['status'] == 'Completed') {
                                            echo '<div class="completed-card-container mb-3">';
                                            echo '<div class="card shadow-sm" style="border-left: 4px solid ' . $color . ';">';
                                            echo '<div class="card-body">';
                                            echo '<div class="row">';
                                            echo '<div class="col-md-8">';
                                            echo '<h5 class="card-title text-primary">' . htmlspecialchars($schedule['customer_name']) . '</h5>';
                                            echo '<div class="row">';
                                            echo '<div class="col-md-6">';
                                            echo '<p class="mb-1"><i class="fas fa-clock text-muted"></i> ' . date('h:i A', strtotime($schedule['schedule_time'])) . '</p>';
                                            echo '<p class="mb-1"><i class="fas fa-cog text-muted"></i> ' . htmlspecialchars($schedule['service_type']) . '</p>';
                                            echo '<p class="mb-1"><i class="fas fa-user text-muted"></i> ' . htmlspecialchars($schedule['installer_name']) . '</p>';
                                            echo '</div>';
                                            echo '<div class="col-md-6">';
                                            echo '<p class="mb-1"><i class="fas fa-map-marker-alt text-muted"></i> ' . htmlspecialchars(substr($schedule['address'], 0, 50)) . '...</p>';
                                            if (!empty($schedule['completed_at'])) {
                                                echo '<p class="mb-1"><i class="fas fa-check-circle text-success"></i> Completed: ' . date('M d, Y g:i A', strtotime($schedule['completed_at'])) . '</p>';
                                            }
                                            echo '</div>';
                                            echo '</div>';
                                            echo '</div>';
                                            echo '<div class="col-md-4 text-right">';
                                            if (!empty($schedule['completion_image'])) {
                                                echo '<img src="../' . htmlspecialchars($schedule['completion_image']) . '" alt="Completion Image" class="completion-thumbnail-large" onclick="viewCompletionImage(\'' . htmlspecialchars($schedule['completion_image']) . '\')">';
                                                echo '<p class="small text-muted mt-1"><i class="fas fa-camera"></i> Completion Photo</p>';
                                            }
                                            echo '<button class="btn btn-primary btn-sm edit-schedule-btn" data-schedule-id="' . $schedule['id'] . '">View Details</button>';
                                            echo '</div>';
                                            echo '</div>';
                                            echo '</div>';
                                            echo '</div>';
                                            echo '</div>';
                                        } else {
                                            // Show other statuses as list items
                                            echo '<div class="list-item" style="border-color: ' . $color . ';">';
                                            echo '<div class="list-info">';
                                            echo '<div class="list-customer">' . htmlspecialchars($schedule['customer_name']) . '</div>';
                                            echo '<div class="list-details">';
                                            echo '<div class="list-detail"><i class="fas fa-clock"></i> ' . date('h:i A', strtotime($schedule['schedule_time'])) . '</div>';
                                            echo '<div class="list-detail"><i class="fas fa-cog"></i> ' . htmlspecialchars($schedule['service_type']) . '</div>';
                                            echo '<div class="list-detail"><i class="fas fa-user"></i> ' . (!empty($schedule['installer_name']) ? htmlspecialchars($schedule['installer_name']) : 'Unassigned') . '</div>';
                                            echo '<div class="list-detail"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars(substr($schedule['address'], 0, 40)) . '...</div>';
                                            echo '<div class="list-detail"><i class="fas fa-info-circle"></i> ' . htmlspecialchars($schedule['status']) . '</div>';
                                            echo '</div>';
                                            echo '</div>';
                                            echo '<div class="card-actions">';
                                            echo '<button class="action-btn btn-primary edit-schedule-btn" data-schedule-id="' . $schedule['id'] . '">View</button>';
                                            echo '<button class="action-btn btn-secondary edit-schedule-btn" data-schedule-id="' . $schedule['id'] . '">Edit</button>';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                    }
                                    
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="empty-state">';
                                echo '<i class="fas fa-calendar-times"></i>';
                                echo '<p>No schedules found for this month.</p>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- End of Content -->

        <?php include('../includes/footer.php'); ?>

    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Assignment Modal -->
<div class="modal fade" id="assignmentModal" tabindex="-1" role="dialog" aria-labelledby="assignmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignmentModalLabel">Assign Schedule</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="assignmentForm" method="POST" action="process_assignment.php">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="selected_date">Selected Date</label>
                                <input type="date" class="form-control" id="selected_date" name="selected_date" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="installer_name">Assign to Installer *</label>
                                <select class="form-control" id="installer_name" name="installer_name" required>
                                    <option value="">Select Installer</option>
                                    <?php
                                    mysqli_data_seek($installers_result, 0);
                                    while ($installer = mysqli_fetch_assoc($installers_result)) {
                                        echo "<option value='" . htmlspecialchars($installer['full_name']) . "'>" . htmlspecialchars($installer['full_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="customer_name">Customer Name *</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contact_number">Contact Number *</label>
                                <input type="tel" class="form-control" id="contact_number" name="contact_number" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Location/Address *</label>
                        <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="schedule_time">Schedule Time *</label>
                                <input type="time" class="form-control" id="schedule_time" name="schedule_time" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="service_type">Service Type *</label>
                                <select class="form-control" id="service_type" name="service_type" required>
                                    <option value="">Select Service Type</option>
                                    <option value="Installation">Installation</option>
                                    <option value="Repair">Repair</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Inspection">Inspection</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="products_to_install">Products to Install *</label>
                        <select class="form-control" id="products_to_install" name="products_to_install" required>
                            <option value="">Select Product</option>
                            <?php
                            // Fetch products from database
                            $products_query = "SELECT p.id, p.product_name, p.capacity, c.category_name, b.brand_name 
                                              FROM products p 
                                              LEFT JOIN category c ON p.category_id = c.category_id 
                                              LEFT JOIN brands b ON p.brand_id = b.brand_id 
                                              WHERE p.quantity > 0 
                                              ORDER BY p.product_name ASC";
                            $products_result = mysqli_query($conn, $products_query);
                            
                            if ($products_result && mysqli_num_rows($products_result) > 0) {
                                while ($product = mysqli_fetch_assoc($products_result)) {
                                    $product_display = $product['product_name'];
                                    if (!empty($product['capacity'])) {
                                        $product_display .= " ({$product['capacity']})";
                                    }
                                    if (!empty($product['brand_name'])) {
                                        $product_display .= " - {$product['brand_name']}";
                                    }
                                    if (!empty($product['category_name'])) {
                                        $product_display .= " [{$product['category_name']}]";
                                    }
                                    echo "<option value='" . htmlspecialchars($product_display) . "'>" . htmlspecialchars($product_display) . "</option>";
                                }
                            } else {
                                echo "<option value=''>No products available</option>";
                            }
                            ?>
                        </select>
                        <small class="form-text text-muted">Select the product to be installed</small>
                    </div>

                    <div class="form-group">
                        <label for="notes">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any special instructions or notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Completion Image Modal -->
<div class="modal fade completion-image-modal" id="completionImageModal" tabindex="-1" role="dialog" aria-labelledby="completionImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="completionImageModalLabel">
                    <i class="fas fa-camera mr-2"></i>Completion Photo
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <img id="completionImageDisplay" src="" alt="Completion Image" class="img-fluid">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div class="modal fade" id="editScheduleModal" tabindex="-1" role="dialog" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editScheduleModalLabel">Edit Schedule</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editScheduleForm" method="POST" action="process_edit_schedule.php">
                <input type="hidden" id="edit_schedule_id" name="schedule_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_installer_name">Installer Name *</label>
                                <select class="form-control" id="edit_installer_name" name="installer_name" required>
                                    <option value="">Select Installer</option>
                                    <?php
                                    mysqli_data_seek($installers_result, 0);
                                    while ($installer = mysqli_fetch_assoc($installers_result)) {
                                        echo "<option value='" . htmlspecialchars($installer['full_name']) . "'>" . htmlspecialchars($installer['full_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_customer_name">Customer Name *</label>
                                <input type="text" class="form-control" id="edit_customer_name" name="customer_name" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_contact_number">Contact Number *</label>
                                <input type="tel" class="form-control" id="edit_contact_number" name="contact_number" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_service_type">Service Type *</label>
                                <select class="form-control" id="edit_service_type" name="service_type" required>
                                    <option value="">Select Service Type</option>
                                    <option value="Installation">Installation</option>
                                    <option value="Repair">Repair</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Inspection">Inspection</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_address">Location/Address *</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="2" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_schedule_date">Schedule Date *</label>
                                <input type="date" class="form-control" id="edit_schedule_date" name="schedule_date" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_schedule_time">Schedule Time *</label>
                                <input type="time" class="form-control" id="edit_schedule_time" name="schedule_time" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_status">Status *</label>
                                <select class="form-control" id="edit_status" name="status" required>
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_products_to_install">Products to Install *</label>
                        <select class="form-control" id="edit_products_to_install" name="products_to_install" required>
                            <option value="">Select Product</option>
                            <?php
                            // Fetch products from database
                            $products_query = "SELECT p.id, p.product_name, p.capacity, c.category_name, b.brand_name 
                                              FROM products p 
                                              LEFT JOIN category c ON p.category_id = c.category_id 
                                              LEFT JOIN brands b ON p.brand_id = b.brand_id 
                                              WHERE p.quantity > 0 
                                              ORDER BY p.product_name ASC";
                            $products_result = mysqli_query($conn, $products_query);
                            
                            if ($products_result && mysqli_num_rows($products_result) > 0) {
                                while ($product = mysqli_fetch_assoc($products_result)) {
                                    $product_display = $product['product_name'];
                                    if (!empty($product['capacity'])) {
                                        $product_display .= " ({$product['capacity']})";
                                    }
                                    if (!empty($product['brand_name'])) {
                                        $product_display .= " - {$product['brand_name']}";
                                    }
                                    if (!empty($product['category_name'])) {
                                        $product_display .= " [{$product['category_name']}]";
                                    }
                                    echo "<option value='" . htmlspecialchars($product_display) . "'>" . htmlspecialchars($product_display) . "</option>";
                                }
                            } else {
                                echo "<option value=''>No products available</option>";
                            }
                            ?>
                        </select>
                        <small class="form-text text-muted">Select the product to be installed</small>
                    </div>

                    <div class="form-group">
                        <label for="edit_notes">Additional Notes</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
                    </div>

                    <!-- Completion Image Display -->
                    <div id="completion_image_section" class="form-group" style="display: none;">
                        <label>Completion Photo</label>
                        <div class="completion-image-display">
                            <img id="edit_completion_image" src="" alt="Completion Image" class="img-fluid rounded" style="max-height: 300px; cursor: pointer;" onclick="viewCompletionImageFromModal()">
                            <p class="text-muted mt-2"><i class="fas fa-camera"></i> Click image to view full size</p>
                        </div>
                    </div>

                    <!-- Employee List Display -->
                    <div id="employee_list_section" class="form-group" style="display: none;">
                        <label>Installation Team</label>
                        <div class="employee-list-display">
                            <div id="edit_employee_list" class="employee-badges"></div>
                            <p class="text-muted mt-2"><i class="fas fa-users"></i> Team members who worked on this installation</p>
                        </div>
                    </div>

                    <!-- Completion Date Display -->
                    <div id="completion_date_section" class="form-group" style="display: none;">
                        <label>Completion Date</label>
                        <p class="form-control-plaintext" id="edit_completed_at"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Bootstrap core JavaScript-->
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
$(document).ready(function() {
    // Ensure completed filter is hidden on initial load
    $('#completed-filter-card').hide();

    // View Toggle Functionality
    $('.view-btn').click(function() {
        $('.view-btn').removeClass('active');
        $(this).addClass('active');
        
        $('#calendar-view, #kanban-view, #list-view').hide();
        
        const view = $(this).data('view');
        if (view === 'calendar') {
            $('#calendar-view').show();
            $('.stats-grid').show();
            $('.filter-card').show();
            $('#completed-filter-card').hide();
        } else if (view === 'kanban') {
            $('#kanban-view').css('display', 'flex').show();
            $('.stats-grid').show();
            $('.filter-card').show();
            $('#completed-filter-card').hide();
        } else if (view === 'list') {
            $('#list-view').show();
            $('.stats-grid').hide();
            $('.filter-card').hide();
            $('#completed-filter-card').show();
        }
    });

    // Calendar day click handler
    $('.calendar-day').click(function() {
        const selectedDate = $(this).data('date');
        $('#selected_date').val(selectedDate);
        $('#assignmentModal').modal('show');
    });

    // Handle assignment form submission
    $('#assignmentForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'process_assignment.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#assignmentModal').modal('hide');
                    showNotification('Schedule assigned successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                showNotification('An error occurred while processing the assignment.', 'error');
            }
        });
    });

    // Handle edit schedule button clicks
    $(document).on('click', '.edit-schedule-btn', function(e) {
        e.stopPropagation();
        const scheduleId = $(this).data('schedule-id');
        
        $.ajax({
            url: 'get_schedule_data.php',
            type: 'GET',
            data: { id: scheduleId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#edit_schedule_id').val(response.data.id);
                    $('#edit_installer_name').val(response.data.installer_name);
                    $('#edit_customer_name').val(response.data.customer_name);
                    $('#edit_contact_number').val(response.data.contact_number);
                    $('#edit_address').val(response.data.address);
                    $('#edit_schedule_date').val(response.data.schedule_date);
                    $('#edit_schedule_time').val(response.data.schedule_time);
                    $('#edit_service_type').val(response.data.service_type);
                    $('#edit_products_to_install').val(response.data.products_to_install);
                    $('#edit_notes').val(response.data.notes);
                    $('#edit_status').val(response.data.status);
                    
                    // Handle completion image and date
                    if (response.data.status === 'Completed' && response.data.completion_image) {
                        $('#edit_completion_image').attr('src', '../' + response.data.completion_image);
                        $('#completion_image_section').show();
                    } else {
                        $('#completion_image_section').hide();
                    }
                    
                    // Handle employee list
                    if (response.data.status === 'Completed' && response.data.employee_list) {
                        const employees = response.data.employee_list.split(/[,\n\r]+/);
                        let employeeBadges = '';
                        employees.forEach(function(employee) {
                            employee = employee.trim();
                            if (employee) {
                                employeeBadges += '<span class="badge badge-secondary mr-1 mb-1">' + employee + '</span>';
                            }
                        });
                        $('#edit_employee_list').html(employeeBadges);
                        $('#employee_list_section').show();
                    } else {
                        $('#employee_list_section').hide();
                    }
                    
                    if (response.data.status === 'Completed' && response.data.completed_at) {
                        $('#edit_completed_at').text(new Date(response.data.completed_at).toLocaleString());
                        $('#completion_date_section').show();
                    } else {
                        $('#completion_date_section').hide();
                    }
                    
                    $('#editScheduleModal').modal('show');
                } else {
                    showNotification('Error loading schedule data: ' + response.message, 'error');
                }
            },
            error: function() {
                showNotification('An error occurred while loading schedule data.', 'error');
            }
        });
    });

    // Function to view completion image
    function viewCompletionImage(imagePath) {
        $('#completionImageDisplay').attr('src', '../' + imagePath);
        $('#completionImageModal').modal('show');
    }

    // Function to view completion image from edit modal
    function viewCompletionImageFromModal() {
        const imageSrc = $('#edit_completion_image').attr('src');
        $('#completionImageDisplay').attr('src', imageSrc);
        $('#completionImageModal').modal('show');
    }

    // Handle edit form submission
    $('#editScheduleForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'process_edit_schedule.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editScheduleModal').modal('hide');
                    showNotification('Schedule updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                showNotification('An error occurred while updating the schedule.', 'error');
            }
        });
    });

    // Drag and Drop for Kanban Cards
    $('.kanban-card').draggable({
        helper: 'clone',
        cursor: 'move',
        revert: 'invalid',
        opacity: 0.7,
        start: function(event, ui) {
            $(this).addClass('dragging');
        },
        stop: function(event, ui) {
            $(this).removeClass('dragging');
        }
    });

    $('.kanban-cards').droppable({
        accept: '.kanban-card',
        hoverClass: 'bg-light',
        drop: function(event, ui) {
            const scheduleId = ui.draggable.data('schedule-id');
            const columnTitle = $(this).closest('.kanban-column').find('.column-title').text().trim();
            
            let statusValue = 'Scheduled';
            if (columnTitle.includes('In Progress')) {
                statusValue = 'In Progress';
            } else if (columnTitle.includes('Completed')) {
                statusValue = 'Completed';
            } else if (columnTitle.includes('Unassigned')) {
                statusValue = 'Scheduled';
            }
            
            // Update status via AJAX
            $.ajax({
                url: 'update_schedule_status.php',
                type: 'POST',
                data: {
                    schedule_id: scheduleId,
                    status: statusValue
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Status updated successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification('Error: ' + response.message, 'error');
                    }
                },
                error: function() {
                    showNotification('Error updating status.', 'error');
                }
            });
        }
    });

    // Notification function
    function showNotification(message, type) {
        const bgColor = type === 'success' ? 
            'linear-gradient(135deg, #48bb78 0%, #38a169 100%)' : 
            'linear-gradient(135deg, #f56565 0%, #e53e3e 100%)';
        
        const icon = type === 'success' ? 
            '<i class="fas fa-check-circle" style="margin-right: 8px;"></i>' : 
            '<i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>';
        
        const notification = $('<div>')
            .css({
                position: 'fixed',
                top: '20px',
                right: '-400px',
                background: bgColor,
                color: 'white',
                padding: '16px 24px',
                borderRadius: '12px',
                boxShadow: '0 8px 24px rgba(0,0,0,0.2)',
                zIndex: 9999,
                display: 'flex',
                alignItems: 'center',
                fontSize: '14px',
                fontWeight: '500'
            })
            .html(icon + message)
            .appendTo('body');
        
        notification.animate({ right: '20px' }, 300);
        
        setTimeout(() => {
            notification.animate({ right: '-400px' }, 300, function() {
                $(this).remove();
            });
        }, 3000);
    }
});
</script>

</body>
</html>