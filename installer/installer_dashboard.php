<?php
session_start();

// Redirect if not logged in as installer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'installer') {
    header("Location: ../login.php");
    exit();
}

include '../config/conn.php';

// Get the logged-in installer's full name from users table
$user_id = $_SESSION['user_id'];
$user_query = "SELECT full_name FROM users WHERE id = ? AND role = 'installer'";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

if (!$user_data) {
    header("Location: ../login.php");
    exit();
}

$installer_name = $user_data['full_name'];

// Get statistics for the installer
$stats_query = "SELECT 
    COUNT(*) as total_schedules,
    SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM installer_schedules WHERE installer_name = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("s", $installer_name);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Fetch schedules for the logged-in installer
$query = "SELECT * FROM installer_schedules 
          WHERE installer_name = ? 
          ORDER BY schedule_date ASC, schedule_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $installer_name);
$stmt->execute();
$result = $stmt->get_result();
$schedules = $result->fetch_all(MYSQLI_ASSOC);

// Group schedules by date
$grouped_schedules = [];
foreach ($schedules as $schedule) {
    $date = $schedule['schedule_date'];
    if (!isset($grouped_schedules[$date])) {
        $grouped_schedules[$date] = [];
    }
    $grouped_schedules[$date][] = $schedule;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no, viewport-fit=cover">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#3b82f6">

    <title>Aircon Dashboard</title>

    <!-- Font Awesome -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,900" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/employee.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 25%, #fef3c7 75%, #fed7aa 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(251, 146, 60, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(168, 85, 247, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="80" height="80" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%233b82f6" fill-opacity="0.03"><circle cx="40" cy="40" r="2"/></g></svg>');
            pointer-events: none;
            z-index: -1;
        }

        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header Section */
        .dashboard-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            color: #1e293b;
            padding: 4rem 3rem;
            border-radius: 32px;
            margin-bottom: 4rem;
            box-shadow: 
                0 20px 60px -12px rgba(0, 0, 0, 0.08),
                0 0 0 1px rgba(59, 130, 246, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 1);
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
                radial-gradient(circle at 30% 30%, rgba(59, 130, 246, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 70% 70%, rgba(251, 146, 60, 0.1) 0%, transparent 40%);
            animation: float 20s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(20px, 20px) rotate(5deg); }
        }

        .dashboard-header::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(60px);
            pointer-events: none;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 2rem;
            position: relative;
            z-index: 2;
        }

        .header-title {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 50%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            z-index: 2;
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { filter: brightness(1); }
            50% { filter: brightness(1.2); }
        }

        .header-subtitle {
            font-size: 1.4rem;
            color: #64748b;
            margin-bottom: 2rem;
            font-weight: 400;
            position: relative;
            z-index: 2;
        }

        .header-stats {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .header-stat {
            text-align: center;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 2rem 1.5rem;
            border-radius: 24px;
            border: 2px solid rgba(59, 130, 246, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.08);
        }

        .header-stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(168, 85, 247, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .header-stat:hover {
            transform: translateY(-8px) scale(1.03);
            background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
            box-shadow: 0 20px 40px -10px rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .header-stat:hover::before {
            opacity: 1;
        }

        .header-stat-number {
            font-size: 3rem;
            font-weight: 900;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .header-stat-label {
            font-size: 1rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 600;
            position: relative;
            z-index: 2;
        }

        /* Main Grid Layout */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        /* Section Headers */
        .section-header {
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            box-shadow: 
                0 4px 20px -5px rgba(0, 0, 0, 0.08),
                0 0 0 1px rgba(59, 130, 246, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(59, 130, 246, 0.1);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-badge {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 4px 12px -2px rgba(59, 130, 246, 0.4);
        }

        /* Grid Layouts */
        .upcoming-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .completed-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        /* Schedule Cards */
        .schedule-card {
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            border-radius: 24px;
            padding: 0;
            box-shadow: 
                0 10px 40px -10px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(59, 130, 246, 0.1);
            border: 2px solid rgba(59, 130, 246, 0.1);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .schedule-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            border-radius: 24px 24px 0 0;
        }

        .schedule-card.scheduled::before { background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%); }
        .schedule-card.in-progress::before { background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%); }
        .schedule-card.completed::before { background: linear-gradient(90deg, #10b981 0%, #34d399 100%); }
        .schedule-card.cancelled::before { background: linear-gradient(90deg, #ef4444 0%, #f87171 100%); }

        .schedule-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 
                0 25px 60px -10px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .schedule-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center, rgba(59, 130, 246, 0.03) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .schedule-card:hover::after {
            opacity: 1;
        }

        /* Card Header with Date */
        .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 2rem;
            border-bottom: 2px solid rgba(226, 232, 240, 0.5);
            position: relative;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6 0%, #8b5cf6 50%, #ec4899 100%);
        }

        .date-time-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .date-display {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .date-day {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        .date-month {
            font-size: 0.9rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .time-badge {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 16px;
            font-weight: 700;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px -2px rgba(59, 130, 246, 0.5);
        }

        .card-content {
            padding: 1.5rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 2px 8px -2px rgba(0, 0, 0, 0.15);
        }

        .status-scheduled { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; }
        .status-in-progress { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; }
        .status-completed { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46; }
        .status-cancelled { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #991b1b; }

        .customer-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .service-type {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: #1e40af;
            padding: 0.25rem 0.75rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px -2px rgba(59, 130, 246, 0.2);
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .info-row i {
            width: 16px;
            color: #94a3b8;
        }

        .product-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .product-tag {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #78350f;
            padding: 0.25rem 0.75rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 500;
            box-shadow: 0 2px 8px -2px rgba(251, 146, 60, 0.2);
        }

        .team-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .team-tag {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 500;
            box-shadow: 0 2px 8px -2px rgba(139, 92, 246, 0.4);
        }

        .image-preview {
            width: 100%;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .image-preview:hover {
            transform: scale(1.02);
        }

        .image-label {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .card-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 2px solid #f1f5f9;
        }

        .action-button {
            flex: 1;
            padding: 0.75rem 1rem;
            border-radius: 16px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .action-button.primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 12px -2px rgba(59, 130, 246, 0.4);
        }

        .action-button.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            box-shadow: 0 4px 12px -2px rgba(245, 158, 11, 0.4);
        }

        .action-button.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px -2px rgba(16, 185, 129, 0.4);
        }

        .action-button.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 12px -2px rgba(239, 68, 68, 0.4);
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.2);
        }

        /* Sliding Container */
        .sliding-container {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            box-shadow: 
                0 10px 40px -10px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(59, 130, 246, 0.08);
            border: 2px solid rgba(59, 130, 246, 0.1);
            touch-action: pan-y pinch-zoom;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        .sliding-wrapper {
            display: flex;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
            width: 200%;
            height: 100%;
        }

        .sliding-page {
            min-width: 50%;
            width: 50%;
            padding: 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            box-sizing: border-box;
        }

        /* Improved touch handling */
        .sliding-container:active {
            cursor: grabbing;
        }

        .sliding-container:hover {
            cursor: grab;
        }

        /* Smooth scrolling for all devices */
        .sliding-wrapper {
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }

        /* Pagination Controls */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            border-radius: 24px;
            margin-top: 2rem;
            box-shadow: 
                0 10px 40px -10px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(59, 130, 246, 0.08);
            border: 2px solid rgba(59, 130, 246, 0.1);
        }

        .pagination-nav {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .pagination-button {
            padding: 0.75rem 1rem;
            border-radius: 16px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px -2px rgba(59, 130, 246, 0.4);
        }

        .pagination-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(59, 130, 246, 0.5);
        }

        .pagination-button:disabled {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            color: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .pagination-info {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
        }

        .pagination-dots {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .pagination-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #cbd5e1;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
        }

        .pagination-dot.active {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            transform: scale(1.3);
            box-shadow: 0 4px 8px -2px rgba(59, 130, 246, 0.4);
        }

        .pagination-dot:hover {
            background: #94a3b8;
            transform: scale(1.1);
        }

        /* Section Tabs */
        .section-tabs {
            display: flex;
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            border-radius: 24px;
            margin-bottom: 2rem;
            box-shadow: 
                0 10px 40px -10px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(59, 130, 246, 0.08);
            border: 2px solid rgba(59, 130, 246, 0.1);
            overflow: hidden;
            padding: 0.5rem;
        }

        .section-tab {
            flex: 1;
            padding: 1rem 2rem;
            background: transparent;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            color: #64748b;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 16px;
        }

        .section-tab.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 12px -2px rgba(59, 130, 246, 0.4);
        }

        .section-tab:hover:not(.active) {
            background: #f8fafc;
            color: #475569;
        }

        /* Auto-play controls */
        .autoplay-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .autoplay-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.9rem;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
        }

        .autoplay-toggle:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .autoplay-toggle.active {
            color: #3b82f6;
            background: #eff6ff;
            border-color: #3b82f6;
        }

        .autoplay-toggle input[type="checkbox"] {
            margin: 0;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            border-radius: 24px;
            box-shadow: 
                0 10px 40px -10px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(59, 130, 246, 0.08);
            border: 2px solid rgba(59, 130, 246, 0.1);
        }

        .empty-state i {
            font-size: 4rem;
            background: linear-gradient(135deg, #cbd5e1 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: #475569;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #64748b;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 24px;
            border: 2px solid rgba(59, 130, 246, 0.1);
            box-shadow: 0 25px 60px -10px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 22px 22px 0 0;
            border: none;
            padding: 1.5rem 2rem;
        }

        .modal-body {
            padding: 2rem;
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px -2px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #475569;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.2);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .sliding-page {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .dashboard-header {
                padding: 2rem 1.5rem;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .header-title {
                font-size: 2.5rem;
            }

            .header-subtitle {
                font-size: 1.1rem;
            }
            
            .header-stats {
                justify-content: center;
                width: 100%;
            }

            .header-stat {
                flex: 1;
                min-width: 120px;
            }
            
            .sliding-page {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
            
            .card-actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .action-button {
                padding: 1rem;
                font-size: 1rem;
            }
            
            .pagination-container {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .pagination-nav {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .pagination-button {
                padding: 1rem 1.5rem;
                font-size: 1rem;
                width: 100%;
                justify-content: center;
            }
            
            .pagination-dots {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .pagination-dot {
                width: 12px;
                height: 12px;
            }
            
            .section-tabs {
                flex-direction: column;
                padding: 0.25rem;
            }
            
            .section-tab {
                padding: 1rem;
                font-size: 1rem;
            }
            
            .autoplay-controls {
                justify-content: center;
            }
            
            .autoplay-toggle {
                padding: 0.75rem 1rem;
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .dashboard-container {
                padding: 0.5rem;
            }
            
            .dashboard-header {
                padding: 1.5rem 1rem;
                border-radius: 20px;
            }
            
            .header-title {
                font-size: 2rem;
            }

            .header-subtitle {
                font-size: 1rem;
            }
            
            .header-stats {
                flex-direction: column;
                gap: 0.75rem;
                width: 100%;
            }

            .header-stat {
                width: 100%;
            }
            
            .header-stat-number {
                font-size: 2rem;
            }
            
            .sliding-page {
                padding: 0.5rem;
                gap: 1rem;
            }
            
            .schedule-card {
                border-radius: 16px;
            }

            .card-header {
                padding: 1.5rem;
            }

            .card-content {
                padding: 1rem;
            }
            
            .customer-name {
                font-size: 1.1rem;
            }
            
            .info-row {
                font-size: 0.85rem;
            }
            
            .product-tags,
            .team-tags {
                gap: 0.25rem;
            }
            
            .product-tag,
            .team-tag {
                font-size: 0.75rem;
                padding: 0.2rem 0.6rem;
            }
            
            .pagination-container {
                padding: 0.75rem;
                border-radius: 16px;
            }
            
            .pagination-button {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
            
            .section-tabs {
                border-radius: 16px;
            }

            .section-tab {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
        }

        /* Touch-friendly improvements */
        @media (hover: none) and (pointer: coarse) {
            .action-button {
                min-height: 44px;
                padding: 0.75rem 1rem;
            }
            
            .pagination-button {
                min-height: 44px;
                padding: 0.75rem 1rem;
            }
            
            .section-tab {
                min-height: 44px;
                padding: 0.75rem 1rem;
            }
            
            .pagination-dot {
                width: 16px;
                height: 16px;
                min-width: 16px;
                min-height: 16px;
            }
        }

        /* High DPI displays */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .schedule-card {
                border-width: 0.5px;
            }
            
            .pagination-dot {
                border-radius: 50%;
            }
        }

        /* Device-specific optimizations */
        @media (orientation: landscape) and (max-height: 500px) {
            .dashboard-header {
                padding: 1.5rem 2rem;
            }
            
            .header-title {
                font-size: 2rem;
            }

            .header-subtitle {
                font-size: 1rem;
            }
            
            .sliding-page {
                padding: 1rem;
            }
            
            .schedule-card {
                padding: 1rem;
            }
        }

        /* iOS Safari specific fixes */
        @supports (-webkit-touch-callout: none) {
            .sliding-container {
                -webkit-overflow-scrolling: touch;
            }
            
            .sliding-wrapper {
                -webkit-transform: translateZ(0);
                transform: translateZ(0);
            }
        }

        /* Android Chrome specific fixes */
        @media screen and (-webkit-min-device-pixel-ratio: 0) {
            .sliding-wrapper {
                -webkit-transform: translate3d(0, 0, 0);
                transform: translate3d(0, 0, 0);
            }
        }

        /* Windows touch devices */
        @media (pointer: coarse) and (hover: none) {
            .action-button:active,
            .pagination-button:active,
            .section-tab:active {
                transform: scale(0.95);
            }
        }

        /* Reduced motion for accessibility */
        @media (prefers-reduced-motion: reduce) {
            .sliding-wrapper {
                transition: none;
            }
            
            .schedule-card:hover {
                transform: none;
            }
            
            .action-button:hover {
                transform: none;
            }

            @keyframes float {
                0%, 100% { transform: none; }
            }

            @keyframes shimmer {
                0%, 100% { filter: none; }
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            }

            .dashboard-header {
                background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(51, 65, 85, 0.9) 100%);
                color: #f1f5f9;
                border-color: rgba(255, 255, 255, 0.1);
            }
            
            .sliding-container {
                background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(51, 65, 85, 0.9) 100%);
                border-color: rgba(255, 255, 255, 0.1);
            }
            
            .schedule-card {
                background: linear-gradient(135deg, rgba(51, 65, 85, 0.95) 0%, rgba(71, 85, 105, 0.9) 100%);
                color: #f1f5f9;
                border-color: rgba(255, 255, 255, 0.1);
            }

            .card-header {
                background: linear-gradient(135deg, rgba(71, 85, 105, 0.5) 0%, rgba(51, 65, 85, 0.5) 100%);
                border-color: rgba(255, 255, 255, 0.1);
            }

            .section-tabs {
                background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(51, 65, 85, 0.9) 100%);
                border-color: rgba(255, 255, 255, 0.1);
            }

            .pagination-container {
                background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(51, 65, 85, 0.9) 100%);
                border-color: rgba(255, 255, 255, 0.1);
            }

            .empty-state {
                background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(51, 65, 85, 0.9) 100%);
                border-color: rgba(255, 255, 255, 0.1);
            }
        }
    </style>
</head>

<body id="page-top">
<div id="wrapper">
    <?php include('includes/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include('includes/topbar.php'); ?>

            <!-- Begin Page Content -->
            <div class="dashboard-container">
                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <div class="header-content">
                        <div>
                            <h1 class="header-title">
                                <i class="fas fa-tools"></i>
                                Installation Dashboard
                            </h1>
                            <p class="header-subtitle">Welcome back, <?php echo htmlspecialchars($installer_name); ?>! Here's your installation schedule overview.</p>
                        </div>
                        <div class="header-stats">
                            <div class="header-stat">
                                <div class="header-stat-number"><?php echo $stats['total_schedules']; ?></div>
                                <div class="header-stat-label">Total Schedules</div>
                            </div>
                            <div class="header-stat">
                                <div class="header-stat-number"><?php echo $stats['scheduled']; ?></div>
                                <div class="header-stat-label">Scheduled</div>
                            </div>
                            <div class="header-stat">
                                <div class="header-stat-number"><?php echo $stats['in_progress']; ?></div>
                                <div class="header-stat-label">In Progress</div>
                            </div>
                            <div class="header-stat">
                                <div class="header-stat-number"><?php echo $stats['completed']; ?></div>
                                <div class="header-stat-label">Completed</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Grid Layout -->
                <div class="main-grid">
                    <?php
                    // Separate upcoming and completed schedules
                    $upcoming_schedules = [];
                    $completed_schedules = [];
                    
                    foreach ($schedules as $schedule) {
                        if ($schedule['status'] === 'Completed') {
                            $completed_schedules[] = $schedule;
                        } else {
                            $upcoming_schedules[] = $schedule;
                        }
                    }
                    
                    // Pagination settings
                    $items_per_page = 6;
                    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $current_page = max(1, $current_page);
                    
                    // Calculate pagination for upcoming schedules
                    $upcoming_total = count($upcoming_schedules);
                    $upcoming_pages = ceil($upcoming_total / $items_per_page);
                    $upcoming_start = ($current_page - 1) * $items_per_page;
                    $upcoming_paginated = array_slice($upcoming_schedules, $upcoming_start, $items_per_page);
                    
                    // Calculate pagination for completed schedules
                    $completed_total = count($completed_schedules);
                    $completed_pages = ceil($completed_total / $items_per_page);
                    $completed_start = ($current_page - 1) * $items_per_page;
                    $completed_paginated = array_slice($completed_schedules, $completed_start, $items_per_page);
                    ?>

                    <!-- Section Tabs -->
                    <div class="section-tabs">
                        <button class="section-tab active" onclick="switchSection('upcoming')">
                            <i class="fas fa-clock"></i>
                            Upcoming (<?php echo $upcoming_total; ?>)
                        </button>
                        <button class="section-tab" onclick="switchSection('completed')">
                            <i class="fas fa-check-circle"></i>
                            Completed (<?php echo $completed_total; ?>)
                        </button>
                    </div>

                    <!-- Sliding Container -->
                    <div class="sliding-container">
                        <div class="sliding-wrapper" id="slidingWrapper">
                            <!-- Upcoming Schedules Page -->
                            <div class="sliding-page" id="upcomingPage">
                                <?php if (empty($upcoming_paginated)): ?>
                                    <div class="empty-state" style="grid-column: 1 / -1;">
                                        <i class="fas fa-calendar-plus"></i>
                                        <h3>No Upcoming Installations</h3>
                                        <p>You don't have any upcoming installations scheduled.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($upcoming_paginated as $schedule): ?>
                                        <?php 
                                            $status_class = strtolower(str_replace(' ', '-', $schedule['status']));
                                            $status_style = '';
                                            switch($schedule['status']) {
                                                case 'In Progress':
                                                    $status_style = 'status-in-progress';
                                                    break;
                                                case 'Cancelled':
                                                    $status_style = 'status-cancelled';
                                                    break;
                                                default:
                                                    $status_style = 'status-scheduled';
                                            }
                                        ?>
                                        <div class="schedule-card <?php echo $status_class; ?>">
                                            <!-- Card Header with Date -->
                                            <div class="card-header">
                                                <div class="date-time-section">
                                                    <div class="date-display">
                                                        <div class="date-day"><?php echo date('d', strtotime($schedule['schedule_date'])); ?></div>
                                                        <div class="date-month"><?php echo date('M Y', strtotime($schedule['schedule_date'])); ?></div>
                                                    </div>
                                                    <div class="time-badge">
                                                        <i class="far fa-clock"></i>
                                                        <?php echo date('g:i A', strtotime($schedule['schedule_time'])); ?>
                                                    </div>
                                                </div>
                                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                                    <span class="status-badge <?php echo $status_style; ?>">
                                                        <?php echo $schedule['status']; ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Card Content -->
                                            <div class="card-content">

                                                <!-- Customer Info -->
                                                <h3 class="customer-name">
                                                    <i class="fas fa-user"></i>
                                                    <?php echo htmlspecialchars($schedule['customer_name']); ?>
                                                </h3>
                                                <span class="service-type">
                                                    <i class="fas fa-tools"></i>
                                                    <?php echo htmlspecialchars($schedule['service_type']); ?>
                                                </span>

                                                <!-- Info Section -->
                                                <div class="info-row">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <span><?php echo htmlspecialchars(substr($schedule['address'], 0, 50)) . (strlen($schedule['address']) > 50 ? '...' : ''); ?></span>
                                                </div>
                                                <div class="info-row">
                                                    <i class="fas fa-phone"></i>
                                                    <span><?php echo htmlspecialchars($schedule['contact_number']); ?></span>
                                                </div>

                                                <!-- Products -->
                                                <?php if (!empty($schedule['products_to_install'])): ?>
                                                <div class="product-tags">
                                                    <?php 
                                                    $products = explode(',', $schedule['products_to_install']);
                                                    foreach($products as $product): 
                                                    ?>
                                                        <span class="product-tag"><?php echo htmlspecialchars(trim($product)); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php endif; ?>

                                                <!-- Notes -->
                                                <?php if (!empty($schedule['notes'])): ?>
                                                <div class="info-row">
                                                    <i class="fas fa-sticky-note"></i>
                                                    <span><?php echo htmlspecialchars(substr($schedule['notes'], 0, 100)) . (strlen($schedule['notes']) > 100 ? '...' : ''); ?></span>
                                                </div>
                                                <?php endif; ?>

                                                <!-- Action Buttons -->
                                                <div class="card-actions">
                                                    <button class="action-button primary" onclick="viewDetails(<?php echo $schedule['id']; ?>)">
                                                        <i class="fas fa-info-circle"></i>
                                                        Details
                                                    </button>
                                                    <?php if ($schedule['status'] == 'Scheduled'): ?>
                                                    <button class="action-button warning" onclick="updateStatus(<?php echo $schedule['id']; ?>, 'In Progress')">
                                                        <i class="fas fa-play"></i>
                                                        Start
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if ($schedule['status'] == 'In Progress'): ?>
                                                    <button class="action-button success" onclick="showCompletionModal(<?php echo $schedule['id']; ?>)">
                                                        <i class="fas fa-check"></i>
                                                        Complete
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if (in_array($schedule['status'], ['Scheduled', 'In Progress'])): ?>
                                                    <button class="action-button danger" onclick="updateStatus(<?php echo $schedule['id']; ?>, 'Cancelled')">
                                                        <i class="fas fa-times"></i>
                                                        Cancel
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Completed Schedules Page -->
                            <div class="sliding-page" id="completedPage">
                                <?php if (empty($completed_paginated)): ?>
                                    <div class="empty-state" style="grid-column: 1 / -1;">
                                        <i class="fas fa-trophy"></i>
                                        <h3>No Completed Installations</h3>
                                        <p>You haven't completed any installations yet.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($completed_paginated as $schedule): ?>
                                        <div class="schedule-card completed">
                                            <!-- Card Header with Date -->
                                            <div class="card-header">
                                                <div class="date-time-section">
                                                    <div class="date-display">
                                                        <div class="date-day"><?php echo date('d', strtotime($schedule['schedule_date'])); ?></div>
                                                        <div class="date-month"><?php echo date('M Y', strtotime($schedule['schedule_date'])); ?></div>
                                                    </div>
                                                    <div class="time-badge">
                                                        <i class="far fa-clock"></i>
                                                        <?php echo date('g:i A', strtotime($schedule['schedule_time'])); ?>
                                                    </div>
                                                </div>
                                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                                    <span class="status-badge status-completed">
                                                        <?php echo $schedule['status']; ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Card Content -->
                                            <div class="card-content">

                                                <!-- Customer Info -->
                                                <h3 class="customer-name">
                                                    <i class="fas fa-user"></i>
                                                    <?php echo htmlspecialchars($schedule['customer_name']); ?>
                                                </h3>
                                                <span class="service-type">
                                                    <i class="fas fa-tools"></i>
                                                    <?php echo htmlspecialchars($schedule['service_type']); ?>
                                                </span>

                                                <!-- Info Section -->
                                                <div class="info-row">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <span><?php echo htmlspecialchars(substr($schedule['address'], 0, 50)) . (strlen($schedule['address']) > 50 ? '...' : ''); ?></span>
                                                </div>
                                                <div class="info-row">
                                                    <i class="fas fa-phone"></i>
                                                    <span><?php echo htmlspecialchars($schedule['contact_number']); ?></span>
                                                </div>

                                                <!-- Products -->
                                                <?php if (!empty($schedule['products_to_install'])): ?>
                                                <div class="product-tags">
                                                    <?php 
                                                    $products = explode(',', $schedule['products_to_install']);
                                                    foreach($products as $product): 
                                                    ?>
                                                        <span class="product-tag"><?php echo htmlspecialchars(trim($product)); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php endif; ?>

                                                <!-- Completion Image -->
                                                <?php if (!empty($schedule['completion_image'])): ?>
                                                <img src="../<?php echo htmlspecialchars($schedule['completion_image']); ?>" 
                                                     alt="Completion Image" 
                                                     class="image-preview"
                                                     onclick="viewImage('../<?php echo htmlspecialchars($schedule['completion_image']); ?>')">
                                                <div class="image-label">Completion Photo</div>
                                                <?php endif; ?>

                                                <!-- Team Members -->
                                                <?php if (!empty($schedule['employee_list'])): ?>
                                                <div class="team-tags">
                                                    <?php 
                                                    $employees = preg_split('/[,\n\r]+/', $schedule['employee_list']);
                                                    foreach($employees as $employee): 
                                                        $employee = trim($employee);
                                                        if (!empty($employee)):
                                                    ?>
                                                        <span class="team-tag"><?php echo htmlspecialchars($employee); ?></span>
                                                    <?php 
                                                        endif;
                                                    endforeach; 
                                                    ?>
                                                </div>
                                                <?php endif; ?>

                                                <!-- Action Buttons -->
                                                <div class="card-actions">
                                                    <button class="action-button primary" onclick="viewDetails(<?php echo $schedule['id']; ?>)">
                                                        <i class="fas fa-info-circle"></i>
                                                        View Details
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Pagination -->
                    <?php if ($upcoming_pages > 1 || $completed_pages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination-nav">
                            <button class="pagination-button" onclick="changePage(<?php echo max(1, $current_page - 1); ?>)" 
                                    <?php echo $current_page <= 1 ? 'disabled' : ''; ?>>
                                <i class="fas fa-chevron-left"></i>
                                Previous
                            </button>
                            
                            <div class="pagination-dots">
                                <?php for ($i = 1; $i <= max($upcoming_pages, $completed_pages); $i++): ?>
                                    <div class="pagination-dot <?php echo $i === $current_page ? 'active' : ''; ?>" 
                                         onclick="changePage(<?php echo $i; ?>)"></div>
                                <?php endfor; ?>
                            </div>
                            
                            <button class="pagination-button" onclick="changePage(<?php echo min(max($upcoming_pages, $completed_pages), $current_page + 1); ?>)"
                                    <?php echo $current_page >= max($upcoming_pages, $completed_pages) ? 'disabled' : ''; ?>>
                                Next
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        
                        <div class="pagination-info">
                            Page <?php echo $current_page; ?> of <?php echo max($upcoming_pages, $completed_pages); ?>
                        </div>
                        
                        <div class="autoplay-controls">
                            <label class="autoplay-toggle" onclick="toggleAutoplay()">
                                <input type="checkbox" id="autoplayToggle">
                                <i class="fas fa-play"></i>
                                Auto-slide
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
            <!-- End of Main Content -->

        </div>

        <?php include('includes/footer.php'); ?>
    </div>
</div>

<!-- Completion Modal -->
<div class="modal fade" id="completionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle mr-2"></i>Complete Installation
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="completionForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Please upload a photo of the completed installation before marking it as complete.
                    </div>
                    
                    <div class="form-group">
                        <label for="completion_image" class="font-weight-bold">
                            <i class="fas fa-camera mr-2"></i>Completion Photo *
                        </label>
                        <input type="file" class="form-control-file" id="completion_image" name="completion_image" 
                               accept="image/*" required>
                        <small class="form-text text-muted">
                            Upload a photo showing the completed installation. Supported formats: JPEG, PNG, GIF (Max 5MB)
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="employee_list" class="font-weight-bold">
                            <i class="fas fa-users mr-2"></i>Installation Team Members *
                        </label>
                        <textarea class="form-control" id="employee_list" name="employee_list" rows="3" 
                                  placeholder="Enter the names of employees who participated in the installation (one per line or separated by commas)" required></textarea>
                        <small class="form-text text-muted">
                            List all team members who worked on this installation. You can separate names with commas or put each name on a new line.
                        </small>
                    </div>
                    
                    <div id="imagePreview" class="mt-3" style="display: none;">
                        <h6>Preview:</h6>
                        <img id="previewImg" src="" alt="Preview" class="img-fluid rounded" style="max-height: 300px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="completeBtn">
                        <i class="fas fa-check mr-2"></i>Complete Installation
                    </button>
                </div>
                <input type="hidden" id="completion_schedule_id" name="schedule_id">
            </form>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Image</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Schedule Image" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function viewDetails(scheduleId) {
    // You can redirect to a details page or open a modal
    window.location.href = 'schedule_details.php?id=' + scheduleId;
}

function updateStatus(scheduleId, newStatus) {
    if (confirm('Update status to "' + newStatus + '"?')) {
        $.ajax({
            url: 'update_schedule_status.php',
            method: 'POST',
            data: {
                schedule_id: scheduleId,
                status: newStatus
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Status updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error updating status. Please try again.');
            }
        });
    }
}

function showCompletionModal(scheduleId) {
    $('#completion_schedule_id').val(scheduleId);
    $('#completionForm')[0].reset();
    $('#imagePreview').hide();
    $('#completionModal').modal('show');
}

// Image preview functionality
$('#completion_image').change(function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#previewImg').attr('src', e.target.result);
            $('#imagePreview').show();
        };
        reader.readAsDataURL(file);
    } else {
        $('#imagePreview').hide();
    }
});

// Handle completion form submission
$('#completionForm').submit(function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const completeBtn = $('#completeBtn');
    
    // Disable button and show loading
    completeBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');
    
    $.ajax({
        url: 'upload_completion_image.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Installation completed successfully with image uploaded!');
                $('#completionModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error uploading image. Please try again.');
        },
        complete: function() {
            // Re-enable button
            completeBtn.prop('disabled', false).html('<i class="fas fa-check mr-2"></i>Complete Installation');
        }
    });
});

function viewImage(imagePath) {
    $('#modalImage').attr('src', imagePath);
    $('#imageModal').modal('show');
}

// Global variables for sliding functionality
let currentSection = 'upcoming';
let autoplayInterval = null;
let isAutoplayEnabled = false;

function switchSection(section) {
    const wrapper = document.getElementById('slidingWrapper');
    const tabs = document.querySelectorAll('.section-tab');
    
    // Update active tab
    tabs.forEach(tab => tab.classList.remove('active'));
    event.target.closest('.section-tab').classList.add('active');
    
    // Slide to appropriate section
    if (section === 'upcoming') {
        wrapper.style.transform = 'translateX(0)';
        currentSection = 'upcoming';
    } else {
        wrapper.style.transform = 'translateX(-50%)';
        currentSection = 'completed';
    }
}

function changePage(page) {
    const url = new URL(window.location);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
}

function toggleAutoplay() {
    const toggle = document.getElementById('autoplayToggle');
    const toggleLabel = document.querySelector('.autoplay-toggle');
    
    isAutoplayEnabled = !isAutoplayEnabled;
    toggle.checked = isAutoplayEnabled;
    
    if (isAutoplayEnabled) {
        toggleLabel.classList.add('active');
        startAutoplay();
    } else {
        toggleLabel.classList.remove('active');
        stopAutoplay();
    }
}

function startAutoplay() {
    if (autoplayInterval) clearInterval(autoplayInterval);
    
    autoplayInterval = setInterval(() => {
        const currentPage = parseInt(new URLSearchParams(window.location.search).get('page')) || 1;
        const maxPages = Math.max(
            <?php echo $upcoming_pages; ?>,
            <?php echo $completed_pages; ?>
        );
        
        if (currentPage < maxPages) {
            changePage(currentPage + 1);
        } else {
            // Switch to other section if at end
            if (currentSection === 'upcoming') {
                const completedTab = document.querySelector('.section-tab:nth-child(2)');
                if (completedTab) completedTab.click();
            } else {
                const upcomingTab = document.querySelector('.section-tab:nth-child(1)');
                if (upcomingTab) upcomingTab.click();
            }
        }
    }, 5000); // Auto-slide every 5 seconds
}

function stopAutoplay() {
    if (autoplayInterval) {
        clearInterval(autoplayInterval);
        autoplayInterval = null;
    }
}

// Initialize sliding functionality
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('slidingWrapper');
    if (wrapper) {
        // Set initial position
        wrapper.style.transform = 'translateX(0)';
    }
    
    // Enhanced touch/swipe support for all devices
    let startX = 0;
    let startY = 0;
    let currentX = 0;
    let currentY = 0;
    let isDragging = false;
    let isHorizontalSwipe = false;
    
    const container = document.querySelector('.sliding-container');
    if (container) {
        // Touch events for mobile devices
        container.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
                isDragging = true;
                isHorizontalSwipe = false;
                e.preventDefault();
            }
        }, { passive: false });
        
        container.addEventListener('touchmove', (e) => {
            if (!isDragging || e.touches.length !== 1) return;
            
            currentX = e.touches[0].clientX;
            currentY = e.touches[0].clientY;
            
            const diffX = Math.abs(currentX - startX);
            const diffY = Math.abs(currentY - startY);
            
            // Determine if this is a horizontal swipe
            if (diffX > diffY && diffX > 10) {
                isHorizontalSwipe = true;
                e.preventDefault();
            }
        }, { passive: false });
        
        container.addEventListener('touchend', (e) => {
            if (!isDragging) return;
            isDragging = false;
            
            if (isHorizontalSwipe) {
                const diff = startX - currentX;
                const threshold = 50;
                
                if (Math.abs(diff) > threshold) {
                    if (diff > 0 && currentSection === 'upcoming') {
                        // Swipe left - go to completed
                        const completedTab = document.querySelector('.section-tab:nth-child(2)');
                        if (completedTab) completedTab.click();
                    } else if (diff < 0 && currentSection === 'completed') {
                        // Swipe right - go to upcoming
                        const upcomingTab = document.querySelector('.section-tab:nth-child(1)');
                        if (upcomingTab) upcomingTab.click();
                    }
                }
            }
        });
        
        // Mouse events for desktop (drag support)
        let isMouseDown = false;
        let mouseStartX = 0;
        let mouseCurrentX = 0;
        
        container.addEventListener('mousedown', (e) => {
            isMouseDown = true;
            mouseStartX = e.clientX;
            e.preventDefault();
        });
        
        container.addEventListener('mousemove', (e) => {
            if (!isMouseDown) return;
            mouseCurrentX = e.clientX;
        });
        
        container.addEventListener('mouseup', (e) => {
            if (!isMouseDown) return;
            isMouseDown = false;
            
            const diff = mouseStartX - mouseCurrentX;
            const threshold = 100; // Higher threshold for mouse
            
            if (Math.abs(diff) > threshold) {
                if (diff > 0 && currentSection === 'upcoming') {
                    const completedTab = document.querySelector('.section-tab:nth-child(2)');
                    if (completedTab) completedTab.click();
                } else if (diff < 0 && currentSection === 'completed') {
                    const upcomingTab = document.querySelector('.section-tab:nth-child(1)');
                    if (upcomingTab) upcomingTab.click();
                }
            }
        });
        
        // Prevent text selection during drag
        container.addEventListener('selectstart', (e) => {
            if (isDragging || isMouseDown) {
                e.preventDefault();
            }
        });
    }
    
    // Keyboard navigation support
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft' && currentSection === 'completed') {
            const upcomingTab = document.querySelector('.section-tab:nth-child(1)');
            if (upcomingTab) upcomingTab.click();
        } else if (e.key === 'ArrowRight' && currentSection === 'upcoming') {
            const completedTab = document.querySelector('.section-tab:nth-child(2)');
            if (completedTab) completedTab.click();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', () => {
        // Recalculate positions on resize
        const wrapper = document.getElementById('slidingWrapper');
        if (wrapper) {
            if (currentSection === 'upcoming') {
                wrapper.style.transform = 'translateX(0)';
            } else {
                wrapper.style.transform = 'translateX(-50%)';
            }
        }
    });
});

// Pause autoplay on hover
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.sliding-container');
    if (container) {
        container.addEventListener('mouseenter', stopAutoplay);
        container.addEventListener('mouseleave', () => {
            if (isAutoplayEnabled) {
                startAutoplay();
            }
        });
    }
});
</script>

</body>
</html>