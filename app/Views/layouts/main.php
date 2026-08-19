<?php
$selectedTheme = session()->get("theme") ?? "system";
$unreadNotificationCount = (new \App\Models\NotificationModel())->unreadCount(
    (int) session()->get("user_id"),
);
$unreadMessageCount = (new \App\Models\DirectConversationModel())->unreadCount(
    (int) session()->get("user_id"),
);
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?= esc($selectedTheme, "attr") ?>">
<?php if (
    $selectedTheme === "system"
): ?><script>
    document.documentElement.dataset.theme = matchMedia('(prefers-color-scheme:dark)').matches ?
        'dark' : 'light';
</script><?php endif; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? "Project Redemption") ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }

        .app {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 240px;
            padding: 24px 16px;
            background: #fff;
            border-right: 1px solid #e5e7eb;
            color: #111827;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .sidebar h2 {
            margin: 0 0 24px;
        }

        .sidebar .sidebar-brand {
            display: inline;
            padding: 0;
            margin: 0;
            color: inherit;
            text-decoration: none;
        }

        .sidebar .sidebar-brand:hover {
            background: transparent;
            color: #111827;
        }

        .menu-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            padding: 0;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: #f8fafc;
            color: #374151;
            cursor: pointer;
        }

        .menu-toggle:hover {
            background: #e5e7eb;
        }

        .menu-toggle:focus-visible {
            outline: 3px solid #60a5fa;
            outline-offset: 2px;
        }

        .menu-icon,
        .menu-icon::before,
        .menu-icon::after {
            display: block;
            width: 21px;
            height: 2px;
            border-radius: 2px;
            background: currentColor;
            transition: transform .2s ease, opacity .2s ease;
        }

        .menu-icon {
            position: relative;
        }

        .menu-icon::before,
        .menu-icon::after {
            position: absolute;
            left: 0;
            content: '';
        }

        .menu-icon::before {
            top: -7px;
        }

        .menu-icon::after {
            top: 7px;
        }

        .menu-toggle[aria-expanded="true"] .menu-icon {
            background: transparent;
        }

        .menu-toggle[aria-expanded="true"] .menu-icon::before {
            top: 0;
            transform: rotate(45deg);
        }

        .menu-toggle[aria-expanded="true"] .menu-icon::after {
            top: 0;
            transform: rotate(-45deg);
        }

        .sidebar a {
            display: block;
            padding: 10px 12px;
            margin-bottom: 6px;
            border-radius: 8px;
            color: #374151;
            text-decoration: none;
        }

        .sidebar a:hover {
            background: #f3f4f6;
            color: #111827;
        }

        .notification-badge {
            display: inline-grid;
            min-width: 20px;
            height: 20px;
            margin-left: 6px;
            padding: 0 6px;
            place-items: center;
            border-radius: 999px;
            background: #dc2626;
            color: #fff;
            font-size: 11px;
            font-weight: 800;
        }

        .main {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
            padding: 32px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        .topbar-account {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        .message-popover-wrap,
        .notification-popover-wrap {
            position: relative
        }

        .message-icon-button {
            position: relative;
            display: grid;
            width: 42px;
            height: 42px;
            padding: 0;
            place-items: center;
            border: 1px solid #d1d5db;
            border-radius: 50%;
            background: #fff;
            color: #374151;
            font-size: 19px;
            cursor: pointer
        }

        .message-icon-button:hover,
        .message-icon-button[aria-expanded="true"] {
            border-color: #93c5fd;
            background: #eff6ff;
            color: #1d4ed8
        }

        .message-icon-button .notification-badge {
            position: absolute;
            top: -5px;
            right: -7px;
            margin: 0
        }

        .message-popover {
            position: absolute;
            top: 50px;
            right: 0;
            z-index: 2000;
            width: min(380px, calc(100vw - 36px));
            overflow: hidden;
            border: 1px solid #d1d5db;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .22);
            text-align: left
        }

        .notification-popover {
            right: -52px
        }

        .message-popover[hidden] {
            display: none
        }

        .message-popover-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb
        }

        .message-popover-head strong {
            font-size: 17px
        }

        .message-popover-head a,
        .message-popover-all {
            color: #2563eb;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700
        }

        .message-preview-list {
            max-height: 390px;
            overflow-y: auto
        }

        .message-preview-item {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-bottom: 1px solid #eef0f3;
            color: inherit;
            text-decoration: none
        }

        .message-preview-item:hover {
            background: #f8fafc
        }

        .message-preview-item.unread {
            background: #eff6ff
        }

        .message-preview-avatar {
            display: grid;
            width: 39px;
            height: 39px;
            place-items: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: #fff;
            font-weight: 800
        }

        .notification-preview-avatar {
            background: #eef2ff;
            color: #4338ca
        }

        .message-preview-copy {
            display: grid;
            min-width: 0;
            gap: 3px
        }

        .message-preview-copy strong,
        .message-preview-copy span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .notification-preview-copy span {
            white-space: normal;
            line-height: 1.35
        }

        .message-preview-copy span,
        .message-preview-meta {
            color: #6b7280;
            font-size: 12px
        }

        .message-preview-meta {
            display: grid;
            justify-items: end;
            gap: 5px;
            white-space: nowrap
        }

        .message-preview-meta b {
            display: grid;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            place-items: center;
            border-radius: 999px;
            background: #2563eb;
            color: #fff;
            font-size: 10px
        }

        .message-popover-state {
            padding: 30px 18px;
            color: #6b7280;
            text-align: center
        }

        .message-popover-all {
            display: block;
            padding: 12px;
            text-align: center;
            background: #f8fafc
        }

        .popover-read-all {
            margin: 0;
            padding: 0;
            border-top: 1px solid #e5e7eb
        }

        .popover-read-all button {
            display: block;
            width: 100%;
            padding: 12px;
            border: 0;
            background: #f8fafc;
            color: #2563eb;
            font: 700 13px Arial, sans-serif;
            text-align: center;
            cursor: pointer
        }

        .popover-read-all button:hover {
            background: #eff6ff
        }

        .account-popover-wrap {
            position: relative
        }

        .account-icon-button {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            border-color: transparent;
            color: #fff;
            font-size: 15px;
            font-weight: 800
        }

        .account-icon-button:hover,
        .account-icon-button[aria-expanded="true"] {
            background: linear-gradient(135deg, #1d4ed8, #6d28d9);
            border-color: #93c5fd;
            color: #fff
        }

        .account-popover {
            width: min(270px, calc(100vw - 36px))
        }

        .account-popover-user {
            display: grid;
            gap: 3px;
            padding: 16px;
            border-bottom: 1px solid #e5e7eb
        }

        .account-popover-user small {
            color: #6b7280
        }

        .account-menu {
            display: grid;
            padding: 7px
        }

        .account-menu a,
        .account-menu button {
            display: flex;
            width: 100%;
            align-items: center;
            gap: 10px;
            padding: 11px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #374151;
            font: inherit;
            text-align: left;
            text-decoration: none;
            cursor: pointer
        }

        .account-menu a:hover,
        .account-menu button:hover {
            background: #f3f4f6
        }

        .account-menu form {
            margin: 0;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb
        }

        .account-menu button {
            color: #dc2626
        }

        .account-menu-icon {
            display: grid;
            width: 24px;
            place-items: center
        }

        .live-toast-stack {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 5000;
            display: grid;
            width: min(360px, calc(100vw - 32px));
            gap: 10px
        }

        .live-toast {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 6px 12px;
            padding: 14px 15px;
            border: 1px solid #bfdbfe;
            border-radius: 13px;
            background: #fff;
            box-shadow: 0 14px 36px rgba(15, 23, 42, .24);
            animation: live-toast-in .2s ease
        }

        .live-toast strong,
        .live-toast p {
            grid-column: 1;
            margin: 0
        }

        .live-toast p {
            overflow-wrap: anywhere;
            color: #4b5563;
            font-size: 13px;
            line-height: 1.45
        }

        .live-toast a {
            grid-column: 1;
            justify-self: start;
            color: #2563eb;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none
        }

        .live-toast button {
            grid-column: 2;
            grid-row: 1;
            width: 28px;
            height: 28px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: #f3f4f6;
            color: #4b5563;
            cursor: pointer
        }

        .live-toast.leaving {
            opacity: 0;
            transform: translateY(8px);
            transition: .18s ease
        }

        @keyframes live-toast-in {
            from {
                opacity: 0;
                transform: translateY(8px)
            }
        }

        .user {
            color: #4b5563;
        }

        .logout {
            padding: 8px 12px;
            border: 0;
            border-radius: 8px;
            background: #dc2626;
            color: #fff;
            cursor: pointer;
        }

        .content {
            min-width: 0;
            max-width: 100%;
            padding: 24px;
            border-radius: 14px;
            background: #fff;
        }

        .content > * {
            min-width: 0;
            max-width: 100%;
        }

        .site-footer {
            width: calc(100% + 240px);
            margin-top: auto;
            margin-left: -240px;
            padding: 28px 12px 4px;
            color: #6b7280;
            text-align: center;
            font-size: 14px;
        }

        .site-footer .heart {
            color: #dc2626;
        }

        .button {
            display: inline-block;
            padding: 10px 14px;
            border: 0;
            border-radius: 8px;
            background: #111827;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
        }

        .button.secondary {
            background: #e5e7eb;
            color: #111827;
        }

        .button.danger {
            background: #dc2626;
        }

        .alert {
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 8px;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    padding: 24px 0 0;
    margin: 0;
    list-style: none;
}

.pagination li {
    margin: 0;
    padding: 0;
    background: transparent;
    border: 0;
}

.pagination li > a {
    display: flex;
    align-items: center;
    justify-content: center;

    min-width: 42px;
    height: 42px;
    padding: 0 14px;

    border: 1px solid #d1d5db;
    border-radius: 9px;

    background: #e5e7eb;
    color: #111827;

    font-weight: 600;
    line-height: 1;

    text-decoration: none;

    transition:
        background .15s ease,
        border-color .15s ease,
        transform .15s ease;
}

        /* CodeIgniter First / Previous / Next / Last
        yazılarını span içine koyuyor.
        Span tekrar buton gibi görünmesin. */
        .pagination li > a > span {
            display: inline;
            min-width: 0;
            height: auto;
            padding: 0;
            margin: 0;

            border: 0;
            border-radius: 0;

            background: transparent;
            color: inherit;

            line-height: inherit;
        }

        .pagination li > a:hover {
            background: #d1d5db;
            border-color: #9ca3af;
            transform: translateY(-1px);
        }

        .pagination li.active > a {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        .pagination li.disabled > a {
            opacity: .45;
            pointer-events: none;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font: inherit;
        }

        textarea {
            min-height: 180px;
            resize: vertical;
        }

        label {
            display: block;
            margin: 16px 0 6px;
            font-weight: 700;
        }

        .speech-input-wrap {
            position: relative;
        }

        .speech-input-wrap input,
        .speech-input-wrap textarea {
            padding-right: 52px;
        }

        .speech-button {
            position: absolute;
            top: 8px;
            right: 8px;
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            padding: 0;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: #fff;
            color: #374151;
            font-size: 18px;
            cursor: pointer;
        }

        .speech-single-line-wrap .speech-button {
            top: 50%;
            transform: translateY(-50%);
        }

        .speech-button:hover {
            background: #f3f4f6;
        }

        .speech-button.listening {
            border-color: #ef4444;
            background: #fee2e2;
            color: #b91c1c;
            animation: speech-pulse 1.2s infinite;
        }

        .speech-button:disabled {
            cursor: not-allowed;
            opacity: .45;
        }

        .speech-status {
            display: block;
            min-height: 18px;
            margin-top: 5px;
            color: #6b7280;
            font-size: 12px;
        }

        .speech-status.error {
            color: #b91c1c;
        }

        @keyframes speech-pulse {
            50% {
                box-shadow: 0 0 0 5px rgba(239, 68, 68, .16);
            }
        }

        .profile-link {
            display: inline-block;
            margin-left: 10px;
            color: #2563eb;
            text-decoration: none;
        }

        .topbar-account .profile-link {
            margin-left: 0;
        }

        html[data-theme="dark"] body {
            background: #0f172a;
            color: #e5e7eb;
        }

        html[data-theme="dark"] .sidebar {
            background: #111827;
            border-right-color: #1f2937;
            color: #fff;
        }

        html[data-theme="dark"] .sidebar .sidebar-brand:hover {
            color: #fff;
        }

        html[data-theme="dark"] .menu-toggle {
            border-color: #374151;
            background: #1f2937;
            color: #fff;
        }

        html[data-theme="dark"] .menu-toggle:hover {
            background: #374151;
        }

        html[data-theme="dark"] .sidebar a {
            color: #d1d5db;
        }

        html[data-theme="dark"] .sidebar a:hover {
            background: #1f2937;
            color: #fff;
        }

        html[data-theme="dark"] .content,
        html[data-theme="dark"] .stat-card,
        html[data-theme="dark"] .panel,
        html[data-theme="dark"] .task-card,
        html[data-theme="dark"] .journal-card,
        html[data-theme="dark"] .settings-card,
        html[data-theme="dark"] .habit-card,
        html[data-theme="dark"] .admin-stat,
        html[data-theme="dark"] .admin-panel {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #e5e7eb;
        }

        html[data-theme="dark"] input,
        html[data-theme="dark"] textarea,
        html[data-theme="dark"] select,
        html[data-theme="dark"] .speech-button {
            background: #0f172a !important;
            border-color: #475569 !important;
            color: #e5e7eb !important;
        }

        html[data-theme="dark"] .button.secondary,
        html[data-theme="dark"] .quick-link {
            background: #334155;
            color: #e5e7eb;
        }

        html[data-theme="dark"] .habit-period,
        html[data-theme="dark"] .dashboard-habit {
            background: #334155 !important;
        }

        html[data-theme="dark"] .mini-day {
            color: #cbd5e1;
        }

        html[data-theme="dark"] .mini-day.has-event:not(.today) {
            background: #1e3a8a;
            color: #dbeafe;
        }

        html[data-theme="dark"] .user,
        html[data-theme="dark"] .site-footer,
        html[data-theme="dark"] .task-description,
        html[data-theme="dark"] .journal-preview {
            color: #94a3b8;
        }

        html[data-theme="dark"] .note-card {
            background: #1e293b;
            border-color: #475569;
        }

        html[data-theme="dark"] .note-title-link,
        html[data-theme="dark"] .note-detail h1,
        html[data-theme="dark"] .task-title {
            color: #f8fafc;
        }

        html[data-theme="dark"] .note-preview,
        html[data-theme="dark"] .note-detail-content {
            color: #e2e8f0;
        }

        html[data-theme="dark"] .notes-intro,
        html[data-theme="dark"] .note-meta,
        html[data-theme="dark"] .task-meta,
        html[data-theme="dark"] .tasks-header p {
            color: #cbd5e1 !important;
        }

        html[data-theme="dark"] .task-card.completed .task-title {
            color: #cbd5e1;
        }

        html[data-theme="dark"] .task-description {
            color: #d1d5db;
        }

        html[data-theme="dark"] .task-filter {
            background: #334155;
            color: #e2e8f0;
        }

        html[data-theme="dark"] .task-filter.active {
            background: #2563eb;
            color: #fff;
        }

        html[data-theme="dark"] .task-check {
            background: #0f172a;
            border-color: #94a3b8;
        }

        html[data-theme="dark"] .task-card.completed .task-check {
            background: #16a34a;
            border-color: #4ade80;
        }

        html[data-theme="dark"] .countdown {
            background: #172554;
            color: #dbeafe;
        }

        html[data-theme="dark"] .countdown-deadline {
            color: #93c5fd;
        }

        html[data-theme="dark"] .countdown.overdue {
            background: #450a0a;
            color: #fecaca;
        }

        html[data-theme="dark"] .priority-high {
            background: #7f1d1d;
            color: #fecaca;
        }

        html[data-theme="dark"] .priority-medium {
            background: #78350f;
            color: #fde68a;
        }

        html[data-theme="dark"] .priority-low {
            background: #14532d;
            color: #bbf7d0;
        }

        html[data-theme="dark"] .latest-notes a {
            color: #f8fafc;
        }

        html[data-theme="dark"] .profile-link,
        html[data-theme="dark"] .panel-title a {
            color: #60a5fa;
        }

        html[data-theme="dark"] .comments-section {
            border-color: #334155;
        }

        html[data-theme="dark"] .comment-form,
        html[data-theme="dark"] .comment-card {
            background: #0f172a;
            border-color: #475569;
        }

        html[data-theme="dark"] .comment-meta,
        html[data-theme="dark"] .comment-form-footer small,
        html[data-theme="dark"] .comments-empty {
            color: #94a3b8;
        }

        html[data-theme="dark"] .comment-meta strong,
        html[data-theme="dark"] .comment-card p {
            color: #e2e8f0;
        }

        html[data-theme="dark"] .admin-head p,
        html[data-theme="dark"] .admin-stat span,
        html[data-theme="dark"] .activity-main small,
        html[data-theme="dark"] .activity-time,
        html[data-theme="dark"] .user-list small,
        html[data-theme="dark"] .log-path {
            color: #94a3b8 !important;
        }

        html[data-theme="dark"] .chart-day strong {
            color: #e2e8f0;
        }

        html[data-theme="dark"] .admin-table th,
        html[data-theme="dark"] .admin-table td,
        html[data-theme="dark"] .log-table th,
        html[data-theme="dark"] .log-table td,
        html[data-theme="dark"] .activity-list li,
        html[data-theme="dark"] .user-list li {
            border-color: #334155;
        }

        html[data-theme="dark"] .log-action {
            background: #312e81;
            color: #c7d2fe;
        }

        html[data-theme="dark"] .pagination li > a {
            background: #334155;
            border-color: #475569;
            color: #e2e8f0;
        }

        html[data-theme="dark"] .pagination li > a > span {
            background: transparent;
            color: inherit;
        }

        html[data-theme="dark"] .pagination li > a:hover {
            background: #475569;
            border-color: #64748b;
        }

        html[data-theme="dark"] .pagination li.active > a {
            background: #2563eb;
            border-color: #3b82f6;
            color: #fff;
        }

        html[data-theme="dark"] .calendar-toolbar,
        html[data-theme="dark"] .calendar-weekday {
            background: #0f172a;
            border-color: #334155;
        }

        html[data-theme="dark"] .date-jump {
            border-color: #334155;
        }

        html[data-theme="dark"] .calendar-day {
            background: #1e293b;
            border-color: #334155;
        }

        html[data-theme="dark"] .calendar-day.outside {
            background: #172033;
        }

        html[data-theme="dark"] .calendar-day.selected {
            background: #422006;
        }

        html[data-theme="dark"] .calendar-grid {
            border-color: #334155;
        }

        html[data-theme="dark"] .day-number,
        html[data-theme="dark"] .calendar-filter-summary,
        html[data-theme="dark"] .calendar-legend {
            color: #cbd5e1;
        }

        html[data-theme="dark"] .message-icon-button,
        html[data-theme="dark"] .message-popover {
            background: #1e293b;
            border-color: #475569;
            color: #e5e7eb
        }

        html[data-theme="dark"] .message-icon-button:hover,
        html[data-theme="dark"] .message-icon-button[aria-expanded="true"],
        html[data-theme="dark"] .message-preview-item.unread {
            background: #172554
        }

        html[data-theme="dark"] .message-popover-head,
        html[data-theme="dark"] .message-preview-item {
            border-color: #334155
        }

        html[data-theme="dark"] .message-preview-item:hover,
        html[data-theme="dark"] .message-popover-all {
            background: #334155
        }

        html[data-theme="dark"] .popover-read-all {
            border-color: #334155
        }

        html[data-theme="dark"] .popover-read-all button {
            background: #334155;
            color: #93c5fd
        }

        html[data-theme="dark"] .popover-read-all button:hover {
            background: #475569
        }

        html[data-theme="dark"] .account-icon-button {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: #fff
        }

        html[data-theme="dark"] .account-popover-user,
        html[data-theme="dark"] .account-menu form {
            border-color: #334155
        }

        html[data-theme="dark"] .account-popover-user small {
            color: #94a3b8
        }

        html[data-theme="dark"] .account-menu a {
            color: #e2e8f0
        }

        html[data-theme="dark"] .account-menu a:hover,
        html[data-theme="dark"] .account-menu button:hover {
            background: #334155
        }

        html[data-theme="dark"] .live-toast {
            border-color: #475569;
            background: #1e293b;
            color: #e5e7eb
        }

        html[data-theme="dark"] .live-toast p {
            color: #cbd5e1
        }

        html[data-theme="dark"] .live-toast button {
            background: #334155;
            color: #e5e7eb
        }

        @media (prefers-color-scheme: dark) {
            html[data-theme="system"] body {
                background: #0f172a;
                color: #e5e7eb;
            }

            html[data-theme="system"] .content {
                background: #1e293b;
            }
        }

        @media (max-width:760px) {
            .content-filter {
                grid-template-columns: 1fr !important;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .topbar-account {
                width: 100%;
                align-self: flex-end;
                justify-content: flex-end;
                flex-wrap: wrap;
                text-align: right;
            }
        }

        @media (max-width: 760px) {
            .app {
                display: block;
            }

            .sidebar {
                width: 100%;
                padding: 12px 16px;
            }

            .sidebar h2 {
                margin: 0;
                font-size: 20px;
            }

            .menu-toggle {
                display: inline-flex;
            }

            .sidebar-nav {
                display: none;
                padding-top: 12px;
            }

            .sidebar-nav.open {
                display: block;
            }

            .sidebar-nav a:last-child {
                margin-bottom: 0;
            }

            .main {
                padding: 18px;
            }

            .site-footer {
                width: 100%;
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="app">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><a class="sidebar-brand" href="<?= site_url(
                "dashboard",
            ) ?>">Project Redemption</a></h2>
                <button class="menu-toggle" type="button" aria-expanded="false"
                    aria-controls="sidebar-navigation" aria-label="Menüyü aç">
                    <span class="menu-icon" aria-hidden="true"></span>
                </button>
            </div>
            <nav class="sidebar-nav" id="sidebar-navigation" aria-label="Ana menü">
                <a href="<?= site_url("dashboard") ?>">Ana Sayfa</a>
                <a href="<?= site_url("notes") ?>">Notlar</a>
                <a href="<?= site_url("calendar") ?>">Takvim</a>
                <a href="<?= site_url("projects") ?>">Projeler</a>
                <a href="<?= site_url("tasks") ?>">Görevler</a>
                <a href="<?= site_url("habits") ?>">Alışkanlık Takibi</a>
                <a href="<?= site_url("timer") ?>">Kronometre</a>
                <a href="<?= site_url("games") ?>">Oyunlar</a>
                <a href="<?= site_url("journal") ?>">Günlük</a>
                <?php if (session()->get("role") === "admin"): ?>
                <a href="<?= site_url("admin") ?>">Admin Dashboard</a>
                <a href="<?= site_url("admin/users") ?>">Kullanıcı Yönetimi</a>
                <a href="<?= site_url("admin/logs") ?>">Aktivite Logları</a>
                <?php endif; ?>
            </nav>
        </aside>

        <main class="main">
            <div class="topbar">
                <strong><?= esc($title ?? "Project Redemption") ?></strong>
                <div class="topbar-account">
                    <div class="notification-popover-wrap">
                        <button class="message-icon-button notification-icon-button" type="button"
                            aria-label="Bildirimleri aç" aria-expanded="false"
                            aria-controls="notification-popover" data-notification-button
                            data-notification-nav data-preview-url="<?= site_url(
                        "notifications/preview",
                    ) ?>">
                            <span aria-hidden="true">🔔</span><?php if (
                            $unreadNotificationCount > 0
                        ): ?><span class="notification-badge"><?= $unreadNotificationCount > 99
    ? "99+"
    : $unreadNotificationCount ?></span><?php endif; ?>
                        </button>
                        <section class="message-popover notification-popover"
                            id="notification-popover" hidden aria-label="Son bildirimler">
                            <header class="message-popover-head"><strong>Bildirimler</strong><a
                                    href="<?= site_url(
                            "notifications",
                        ) ?>">Tümünü gör</a></header>
                            <div class="message-preview-list" data-notification-preview-list>
                                <div class="message-popover-state">Bildirimler yükleniyor…</div>
                            </div>
                            <form class="popover-read-all" method="post" action="<?= site_url(
                            "notifications/read-all",
                        ) ?>"><?= csrf_field() ?><button type="submit">Tümünü okundu
                                    işaretle</button></form>
                        </section>
                    </div>
                    <div class="message-popover-wrap">
                        <button class="message-icon-button" type="button" aria-label="Mesajları aç"
                            aria-expanded="false" aria-controls="message-popover" data-message-nav
                            data-preview-url="<?= site_url(
                        "messages/preview",
                    ) ?>">
                            <span aria-hidden="true">✉</span><?php if (
                            $unreadMessageCount > 0
                        ): ?><span class="notification-badge"><?= $unreadMessageCount > 99
    ? "99+"
    : $unreadMessageCount ?></span><?php endif; ?>
                        </button>
                        <section class="message-popover" id="message-popover" hidden
                            aria-label="Son mesajlar">
                            <header class="message-popover-head"><strong>Mesajlar</strong><a href="<?= site_url(
                            "messages",
                        ) ?>">Tümünü gör</a></header>
                            <div class="message-preview-list" data-message-preview-list>
                                <div class="message-popover-state">Konuşmalar yükleniyor…</div>
                            </div>
                            <form class="popover-read-all" method="post" action="<?= site_url(
                            "messages/read-all",
                        ) ?>"><?= csrf_field() ?><button type="submit">Tümünü okundu
                                    işaretle</button></form>
                        </section>
                    </div>
                    <div class="account-popover-wrap">
                        <button class="message-icon-button account-icon-button" type="button"
                            aria-label="Hesap menüsünü aç" aria-expanded="false"
                            aria-controls="account-popover" data-account-button><?= esc(
                        mb_strtoupper(mb_substr((string) session()->get("username"), 0, 1)),
                    ) ?></button>
                        <section class="message-popover account-popover" id="account-popover" hidden
                            aria-label="Hesap menüsü">
                            <div class="account-popover-user"><strong><?= esc(
                            session()->get("username"),
                        ) ?></strong><small><?= session()->get("role") === "admin"
    ? "Admin hesabı"
    : "Kullanıcı hesabı" ?></small></div>
                            <div class="account-menu">
                                <a href="<?= site_url(
                                "users/" . session()->get("user_id"),
                            ) ?>"><span class="account-menu-icon"
                                        aria-hidden="true">●</span>Profilim</a>
                                <a href="<?= site_url(
                                "profile",
                            ) ?>"><span class="account-menu-icon"
                                        aria-hidden="true">⚙</span>Ayarlar</a>
                                <form method="post" action="<?= site_url(
                                "logout",
                            ) ?>"><?= csrf_field() ?><button type="submit"><span
                                            class="account-menu-icon"
                                            aria-hidden="true">↪</span>Çıkış Yap</button></form>
                            </div>
                        </section>
                    </div>
                </div>
            </div>

            <div class="content">
                <?php if ($error = session()->getFlashdata("error")): ?>
                <div class="alert error"><?= esc($error) ?></div>
                <?php endif; ?>
                <?= $this->renderSection("content") ?>
            </div>

            <footer class="site-footer">
                Made with <span class="heart" aria-label="love">♥</span> by Halide.
            </footer>
        </main>
    </div>
    <div class="live-toast-stack" data-live-toast-stack data-status-url="<?= site_url(
    "system/live-updates",
) ?>" aria-live="polite" aria-atomic="false"></div>
    <div data-realtime-client data-token-url="<?= site_url("system/realtime-token") ?>"></div>
    <script src="<?= base_url("js/speech-input.js") ?>"></script>
    <script src="<?= base_url("js/message-popover.js") ?>"></script>
    <script src="<?= base_url("js/notification-popover.js") ?>"></script>
    <script src="<?= base_url("js/account-popover.js") ?>"></script>
    <script src="<?= base_url("js/realtime-client.js") ?>?v=<?= filemtime(
    FCPATH . "js/realtime-client.js",
) ?>"></script>
    <script src="<?= base_url("js/live-updates.js") ?>"></script>
    <?php if ($draftKey = session()->getFlashdata("clearJournalDraft")): ?>
    <script>
        try {
            localStorage.removeItem(<?= json_encode(
                $draftKey,
            ) ?>)
        } catch (error) {
            /* Depolama kullanılamıyorsa kayıt yine tamamlanmıştır. */ }
    </script>
    <?php endif; ?>
    <script>
        (() => {
            const button = document.querySelector('.menu-toggle');
            const navigation = document.querySelector('.sidebar-nav');
            if (!button || !navigation) return;

            const closeMenu = () => {
                navigation.classList.remove('open');
                button.setAttribute('aria-expanded', 'false');
                button.setAttribute('aria-label', 'Menüyü aç');
            };

            button.addEventListener('click', () => {
                const isOpen = navigation.classList.toggle('open');
                button.setAttribute('aria-expanded', String(isOpen));
                button.setAttribute('aria-label', isOpen ? 'Menüyü kapat' :
                'Menüyü aç');
            });

            navigation.addEventListener('click', event => {
                if (event.target.closest('a') && window.innerWidth <= 760) closeMenu();
            });

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') closeMenu();
            });
        })();
    </script>
    <script>
        localStorage.setItem('project-redemption-theme', <?= json_encode(
            $selectedTheme,
        ) ?>);
        localStorage.setItem('project-redemption-theme-default-v2', '1');
    </script>
    <script>
        (() => {
            let realtimeConnected = false;
            let heartbeatTimer = null;

            const heartbeat = () => {
                window.clearTimeout(heartbeatTimer);

                if (!document.hidden && !realtimeConnected) {
                    fetch(<?= json_encode(
                        site_url("system/heartbeat"),
                    ) ?>, {
                        cache: 'no-store',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).catch(() => {});
                }

                heartbeatTimer = window.setTimeout(heartbeat, 30000);
            };

            document.addEventListener('project:realtime-connected', () => {
                realtimeConnected = true;
            });
            document.addEventListener('project:realtime-disconnected', () => {
                realtimeConnected = false;
                heartbeat();
            });
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && !realtimeConnected) heartbeat();
            });
            window.addEventListener(
                'pagehide',
                () => window.clearTimeout(heartbeatTimer),
                { once: true }
            );

            heartbeat();
        })();
    </script>
    <?= view("partials/update_notifier", [
    "codeVersion" => (new \App\Libraries\CodeVersion())->current(),
]) ?>
</body>

</html>
