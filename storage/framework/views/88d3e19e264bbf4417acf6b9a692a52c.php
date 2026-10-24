
<style>
    /* ---------- Row actions: labelled buttons + "More" menu ----------
       <div class="ac-actions">
           <button class="btn btn-sm btn-soft-success"><iconify-icon icon="..."></iconify-icon><span>Pay</span></button>
           <div class="dropdown"> <button class="btn btn-sm btn-soft-secondary ac-more" data-bs-toggle="dropdown">...</button> <ul class="dropdown-menu dropdown-menu-end">...</ul></div>
       </div> */
    .ac-actions {
        display: flex;
        gap: 6px;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: nowrap;
    }

    .ac-actions .btn,
    .ac-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        font-weight: 500;
        white-space: nowrap;
        line-height: 1.2;
    }

    .ac-actions .btn iconify-icon,
    .ac-btn iconify-icon {
        font-size: 16px;
    }

    .ac-actions .ac-more {
        width: 32px;
        padding-left: 0;
        padding-right: 0;
    }

    .ac-actions .ac-more iconify-icon {
        font-size: 18px;
    }

    /* while a row menu is open its table stops clipping, so the menu is never cut off on the last rows */
    .table-responsive:has(.ac-actions .dropdown-menu.show) {
        overflow: visible;
    }

    .dropdown-menu .dropdown-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding-top: .5rem;
        padding-bottom: .5rem;
    }

    .dropdown-menu .dropdown-item iconify-icon {
        font-size: 18px;
        opacity: .85;
    }

    /* ---------- Player cell (photo + name + sub line) ---------- */
    .ac-person {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        color: inherit;
        text-decoration: none;
    }

    .ac-person img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }

    .ac-person .nm {
        font-weight: 600;
        line-height: 1.25;
        color: var(--bs-emphasis-color, #1e293b);
    }

    .ac-person .sub {
        font-size: 12px;
        color: #64748b;
    }

    /* ---------- Small polish everywhere ---------- */
    .table > :not(caption) > * > * {
        vertical-align: middle;
    }

    .form-label {
        font-weight: 500;
    }

    .card-header .card-subtitle {
        margin-top: 2px;
    }

    /* ---------- Phones (most staff use the system on a phone) ---------- */
    @media (max-width: 575.98px) {
        .page-content .container-fluid,
        .page-content .container-xxl {
            padding-left: 10px;
            padding-right: 10px;
        }

        .card-body,
        .card-header {
            padding: .9rem;
        }

        .table > :not(caption) > * > * {
            padding: .55rem .45rem;
        }

        .ac-actions .btn span.ac-hide-xs {
            display: none;
        }

        .ac-person {
            gap: 8px;
        }

        .ac-person img,
        .sc-list .ac-person img {
            width: 32px;
            height: 32px;
        }

        /* lists with 3 row buttons (players): no photo on phones, the name and ID are enough */
        .ac-photo-sm-only img {
            display: none;
        }

        /* badges tucked under a name on phones: only as wide as their text */
        .ac-person .badge,
        .ds-player .badge {
            width: auto !important;
            padding: 4px 8px !important;
        }

        .modal-footer .btn {
            flex: 1;
        }

        h4.page-title,
        .page-title-box h4 {
            font-size: 1.05rem;
        }

        /* bigger tap targets for form fields */
        .form-control,
        .form-select {
            min-height: 44px;
            font-size: 16px; /* stops iPhone zooming into fields */
        }

        .btn {
            min-height: 38px;
        }

        .btn-sm {
            min-height: 34px;
        }
    }
</style>
<?php /**PATH F:\Nexas\ms-academy\resources\views/partials/app-ui.blade.php ENDPATH**/ ?>