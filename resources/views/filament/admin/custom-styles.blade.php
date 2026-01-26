<style>
    /* Custom Filament Panel Styling - Beautiful, Interactive & Animated */
    :root {
        --sidebar-width: 14rem;
        --primary-color: rgb(34 197 94);
        --primary-dark: rgb(22 163 74);
    }

    /* Smooth page transitions */
    * {
        transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }

    /* Animated sidebar */
    .fi-sidebar {
        width: var(--sidebar-width) !important;
        min-width: var(--sidebar-width) !important;
        max-width: var(--sidebar-width) !important;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
        border-right: 1px solid rgb(226 232 240) !important;
        box-shadow: 2px 0 8px rgba(0, 0, 0, 0.04) !important;
        animation: slideInLeft 0.4s ease-out !important;
    }

    @keyframes slideInLeft {
        from {
            transform: translateX(-100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Beautiful brand name with animation */
    .fi-sidebar-brand {
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        color: var(--primary-color) !important;
        letter-spacing: -0.02em !important;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: fadeInDown 0.6s ease-out !important;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Interactive sidebar navigation items */
    .fi-sidebar-nav-item {
        font-size: 0.9375rem !important;
        line-height: 1.5 !important;
        font-weight: 500 !important;
        border-radius: 0.5rem !important;
        margin-bottom: 0.25rem !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        position: relative !important;
        overflow: hidden !important;
    }

    .fi-sidebar-nav-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 3px;
        background: var(--primary-color);
        transform: scaleY(0);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .fi-sidebar-nav-item:hover {
        background-color: rgb(240 253 244) !important;
        transform: translateX(4px) !important;
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.15) !important;
    }

    .fi-sidebar-nav-item:hover::before {
        transform: scaleY(1);
    }

    .fi-sidebar-nav-item-label {
        color: rgb(30 41 59) !important;
        font-size: 0.9375rem !important;
        transition: color 0.3s ease !important;
    }

    .fi-sidebar-nav-item:hover .fi-sidebar-nav-item-label {
        color: var(--primary-color) !important;
        font-weight: 600 !important;
    }

    /* Active state with animation */
    .fi-sidebar-nav-item-active {
        background: linear-gradient(90deg, rgb(220 252 231) 0%, rgb(240 253 244) 100%) !important;
        border-left: 3px solid var(--primary-color) !important;
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.2) !important;
        animation: pulse 2s infinite !important;
    }

    @keyframes pulse {
        0%, 100% {
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.2);
        }
        50% {
            box-shadow: 0 2px 12px rgba(34, 197, 94, 0.3);
        }
    }

    .fi-sidebar-nav-item-active .fi-sidebar-nav-item-label {
        color: var(--primary-color) !important;
        font-weight: 600 !important;
    }

    /* Animated topbar */
    .fi-topbar {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
        border-bottom: 1px solid rgb(21 128 61) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        height: 4rem !important;
        animation: slideDown 0.4s ease-out !important;
    }

    @keyframes slideDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Main content area with smooth background */
    .fi-main {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
        padding: 1.5rem !important;
        animation: fadeIn 0.5s ease-out !important;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    /* Beautiful cards with hover effects */
    .fi-section {
        background-color: #ffffff !important;
        border: 1px solid rgb(226 232 240) !important;
        border-radius: 1rem !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        overflow: hidden !important;
        position: relative !important;
    }

    .fi-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .fi-section:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
        border-color: rgb(203 213 225) !important;
    }

    .fi-section:hover::before {
        transform: scaleX(1);
    }

    /* Animated stats widgets */
    .fi-stats-overview-stat {
        font-size: 2rem !important;
        font-weight: 700 !important;
        color: rgb(15 23 42) !important;
        line-height: 1.2 !important;
        animation: countUp 0.8s ease-out !important;
    }

    @keyframes countUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fi-stats-overview-stat-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        border-radius: 0.75rem !important;
        overflow: hidden !important;
        position: relative !important;
    }

    .fi-stats-overview-stat-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.05) 0%, rgba(22, 163, 74, 0.05) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .fi-stats-overview-stat-card:hover {
        transform: translateY(-4px) scale(1.02) !important;
        box-shadow: 0 12px 24px rgba(34, 197, 94, 0.15) !important;
    }

    .fi-stats-overview-stat-card:hover::after {
        opacity: 1;
    }

    /* Beautiful buttons with animations */
    .fi-btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
        border: none !important;
        color: white !important;
        font-weight: 600 !important;
        border-radius: 0.5rem !important;
        padding: 0.625rem 1.25rem !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3) !important;
        position: relative !important;
        overflow: hidden !important;
    }

    .fi-btn-primary::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .fi-btn-primary:hover {
        transform: translateY(-2px) scale(1.05) !important;
        box-shadow: 0 8px 16px rgba(34, 197, 94, 0.4) !important;
    }

    .fi-btn-primary:hover::before {
        width: 300px;
        height: 300px;
    }

    .fi-btn-primary:active {
        transform: translateY(0) scale(1) !important;
    }

    /* Interactive table rows */
    .fi-ta-row {
        transition: all 0.2s ease !important;
    }

    .fi-ta-row:hover {
        background-color: rgb(240 253 244) !important;
        transform: scale(1.01) !important;
    }

    /* Form inputs with focus animations */
    .fi-input input,
    .fi-input textarea,
    .fi-input select {
        border-radius: 0.5rem !important;
        border: 2px solid rgb(203 213 225) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .fi-input input:focus,
    .fi-input textarea:focus,
    .fi-input select:focus {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1) !important;
        transform: scale(1.01) !important;
    }

    /* Badge animations */
    .fi-badge {
        animation: fadeInScale 0.3s ease-out !important;
        transition: all 0.2s ease !important;
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .fi-badge:hover {
        transform: scale(1.1) !important;
    }

    /* Chart animations */
    canvas {
        animation: fadeIn 0.8s ease-out !important;
    }

    /* Loading states */
    .fi-loading {
        animation: spin 1s linear infinite !important;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    /* Section headers with gradient */
    .fi-section-header {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
        border-bottom: 2px solid rgb(226 232 240) !important;
        padding: 1.25rem 1.75rem !important;
    }

    .fi-section-header-heading {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        background: linear-gradient(135deg, rgb(15 23 42) 0%, rgb(30 41 59) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Dark mode enhancements */
    .dark .fi-sidebar {
        background: linear-gradient(180deg, rgb(15 23 42) 0%, rgb(30 41 59) 100%) !important;
    }

    .dark .fi-main {
        background: linear-gradient(135deg, rgb(15 23 42) 0%, rgb(30 41 59) 100%) !important;
    }

    .dark .fi-section {
        background-color: rgb(30 41 59) !important;
        border-color: rgb(51 65 85) !important;
    }

    .dark .fi-section:hover {
        border-color: rgb(71 85 105) !important;
    }

    .dark .fi-topbar {
        background: linear-gradient(135deg, var(--primary-dark) 0%, rgb(21 128 61) 100%) !important;
    }

    /* Smooth scroll */
    html {
        scroll-behavior: smooth !important;
    }

    /* Page transitions */
    .fi-page {
        animation: fadeInUp 0.4s ease-out !important;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Notification animations */
    .fi-notification {
        animation: slideInRight 0.4s ease-out !important;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Modal animations */
    .fi-modal {
        animation: fadeInScale 0.3s ease-out !important;
    }

    /* Tooltip animations */
    .fi-tooltip {
        animation: fadeIn 0.2s ease-out !important;
    }

    /* Progress bars */
    .fi-progress-bar {
        animation: progressFill 1s ease-out !important;
    }

    @keyframes progressFill {
        from {
            width: 0;
        }
    }
</style>
