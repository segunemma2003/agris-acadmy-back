<style>
    /* Custom Filament Panel Styling - Green & White Theme */
    :root {
        --sidebar-width: 14rem; /* Reduced from default 20rem */
    }

    /* Reduce sidebar width - More compact sidebar while keeping it visible */
    .fi-sidebar {
        width: var(--sidebar-width) !important;
        min-width: var(--sidebar-width) !important;
        max-width: var(--sidebar-width) !important;
        display: flex !important;
        flex-direction: column !important;
    }

    .fi-sidebar-nav {
        padding: 0.875rem 0.625rem !important;
        gap: 0.375rem !important;
    }

    .fi-sidebar-header {
        padding: 1.25rem 0.875rem !important;
    }

    /* Beautiful brand name styling */
    .fi-sidebar-brand {
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        color: rgb(34 197 94) !important;
        letter-spacing: -0.02em !important;
    }

    /* Improve text readability with beautiful styling */
    .fi-sidebar-nav-item {
        font-size: 0.9375rem !important;
        line-height: 1.5 !important;
        font-weight: 500 !important;
        border-radius: 0.5rem !important;
        margin-bottom: 0.25rem !important;
        transition: all 0.2s ease !important;
    }

    .fi-sidebar-nav-item:hover {
        background-color: rgb(240 253 244) !important; /* green-50 */
    }

    .fi-sidebar-nav-item-label {
        color: rgb(30 41 59) !important; /* slate-800 for better contrast */
        font-size: 0.9375rem !important;
        line-height: 1.5 !important;
        transition: color 0.2s ease !important;
    }

    .fi-sidebar-nav-item-icon {
        width: 1.25rem !important;
        height: 1.25rem !important;
        transition: color 0.2s ease !important;
    }

    /* Improve dashboard text readability with beautiful design */
    .fi-main {
        background-color: #f8fafc !important; /* slate-50 for softer background */
        padding: 1.5rem !important;
    }

    .fi-section-header-heading {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        color: rgb(15 23 42) !important; /* slate-900 */
        line-height: 1.4 !important;
        letter-spacing: -0.01em !important;
    }

    .fi-section-content {
        color: rgb(51 65 85) !important; /* slate-700 for better readability */
        line-height: 1.7 !important;
        font-size: 0.9375rem !important;
    }

    /* Table text readability */
    .fi-ta-cell {
        font-size: 0.9375rem !important;
        line-height: 1.5 !important;
        color: rgb(30 41 59) !important; /* slate-800 */
    }

    .fi-ta-header-cell {
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        color: rgb(15 23 42) !important; /* slate-900 */
        line-height: 1.5 !important;
    }

    /* Form label readability */
    .fi-fo-field-wrp-label {
        font-size: 0.9375rem !important;
        font-weight: 600 !important;
        color: rgb(15 23 42) !important; /* slate-900 */
        line-height: 1.5 !important;
        margin-bottom: 0.5rem !important;
    }

    /* Input text readability */
    input[type="text"],
    input[type="email"],
    input[type="number"],
    input[type="password"],
    textarea,
    select {
        font-size: 0.9375rem !important;
        line-height: 1.5 !important;
        color: rgb(15 23 42) !important; /* slate-900 */
    }

    /* Widget text readability */
    .fi-stats-overview-stat {
        font-size: 2rem !important;
        font-weight: 700 !important;
        color: rgb(15 23 42) !important; /* slate-900 */
        line-height: 1.2 !important;
    }

    .fi-stats-overview-stat-label {
        font-size: 0.875rem !important;
        font-weight: 500 !important;
        color: rgb(71 85 105) !important; /* slate-600 */
        line-height: 1.5 !important;
    }

    /* Green primary color enhancements with beautiful effects */
    .fi-btn-primary {
        background-color: rgb(34 197 94) !important; /* green-500 */
        border-color: rgb(34 197 94) !important;
        color: white !important;
        font-weight: 600 !important;
        border-radius: 0.5rem !important;
        padding: 0.625rem 1.25rem !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
    }

    .fi-btn-primary:hover {
        background-color: rgb(22 163 74) !important; /* green-600 */
        border-color: rgb(22 163 74) !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
    }

    .fi-sidebar-nav-item-active {
        background-color: rgb(220 252 231) !important; /* green-100 */
        border-left: 3px solid rgb(34 197 94) !important;
    }

    .fi-sidebar-nav-item-active .fi-sidebar-nav-item-label {
        color: rgb(34 197 94) !important; /* green-500 */
        font-weight: 600 !important;
    }

    .fi-sidebar-nav-item-active .fi-sidebar-nav-item-icon {
        color: rgb(34 197 94) !important; /* green-500 */
    }

    /* Badge and status colors with green theme */
    .fi-badge-primary {
        background-color: rgb(220 252 231) !important; /* green-100 */
        color: rgb(22 101 52) !important; /* green-800 */
        font-weight: 500 !important;
    }

    /* Improve card/section readability with beautiful design */
    .fi-section {
        background-color: #ffffff !important;
        border: 1px solid rgb(226 232 240) !important; /* slate-200 */
        border-radius: 0.75rem !important;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1) !important;
        transition: box-shadow 0.2s ease !important;
    }

    .fi-section:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1) !important;
    }

    .fi-section-header {
        border-bottom: 1px solid rgb(226 232 240) !important; /* slate-200 */
        padding: 1.25rem 1.75rem !important;
        background: linear-gradient(to right, #ffffff, #f8fafc) !important;
    }

    .fi-section-content-ctn {
        padding: 1.75rem !important;
    }

    /* Branding - Green theme accents with beautiful design */
    .fi-topbar {
        background: linear-gradient(135deg, rgb(34 197 94) 0%, rgb(22 163 74) 100%) !important; /* green-500 to green-600 */
        border-bottom: 1px solid rgb(21 128 61) !important; /* green-700 */
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1) !important;
        height: 4rem !important;
    }

    .fi-sidebar {
        background-color: #ffffff !important;
        border-right: 1px solid rgb(226 232 240) !important; /* slate-200 */
        box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.05) !important;
    }

    /* Dark mode adjustments for better readability */
    .dark .fi-main {
        background-color: rgb(15 23 42) !important; /* slate-900 */
    }
    
    .dark .fi-sidebar-nav-item-label {
        color: rgb(226 232 240) !important; /* slate-200 */
    }
    
    .dark .fi-section-header-heading {
        color: rgb(248 250 252) !important; /* slate-50 */
    }
    
    .dark .fi-section-content {
        color: rgb(203 213 225) !important; /* slate-300 */
    }
    
    .dark .fi-ta-cell {
        color: rgb(226 232 240) !important; /* slate-200 */
    }
    
    .dark .fi-ta-header-cell {
        color: rgb(248 250 252) !important; /* slate-50 */
    }
    
    .dark .fi-fo-field-wrp-label {
        color: rgb(248 250 252) !important; /* slate-50 */
    }
    
    .dark input[type="text"],
    .dark input[type="email"],
    .dark input[type="number"],
    .dark input[type="password"],
    .dark textarea,
    .dark select {
        color: rgb(248 250 252) !important; /* slate-50 */
        background-color: rgb(30 41 59) !important; /* slate-800 */
    }
    
    .dark .fi-section {
        background-color: rgb(30 41 59) !important; /* slate-800 */
        border-color: rgb(51 65 85) !important; /* slate-700 */
    }
    
    .dark .fi-section-header {
        border-bottom-color: rgb(51 65 85) !important; /* slate-700 */
    }
    
    .dark .fi-sidebar {
        background-color: rgb(15 23 42) !important; /* slate-900 */
        border-right-color: rgb(51 65 85) !important; /* slate-700 */
    }
    
    .dark .fi-topbar {
        background: linear-gradient(135deg, rgb(22 163 74) 0%, rgb(21 128 61) 100%) !important; /* green-600 to green-700 */
        border-bottom-color: rgb(20 83 45) !important; /* green-800 */
    }

    .dark .fi-sidebar-brand {
        color: rgb(74 222 128) !important; /* green-400 */
    }

    /* Widget enhancements */
    .fi-stats-overview {
        gap: 1rem !important;
    }

    /* Table enhancements */
    .fi-ta-table {
        border-radius: 0.5rem !important;
        overflow: hidden !important;
    }

    /* Form input enhancements */
    .fi-input input,
    .fi-input textarea,
    .fi-input select {
        border-radius: 0.5rem !important;
        border: 1px solid rgb(203 213 225) !important; /* slate-300 */
        transition: all 0.2s ease !important;
    }

    .fi-input input:focus,
    .fi-input textarea:focus,
    .fi-input select:focus {
        border-color: rgb(34 197 94) !important;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1) !important;
    }
</style>

