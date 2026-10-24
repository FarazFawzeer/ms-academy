
<style>
    /* colours taken from the academy logo: deep navy + gold */
    :root {
        --lg-bg: #07142b;
        --lg-card: #0d1f3f;
        --lg-field: #0a1933;
        --lg-line: rgba(148, 178, 222, .14);
        --lg-line-strong: rgba(148, 178, 222, .26);
        --lg-text: #e6edf7;
        --lg-heading: #ffffff;
        --lg-muted: #9fb0c8;
        --lg-faint: #6b7f9e;
        --lg-gold: #f5b301;
        --lg-gold-hover: #ffc21a;
        --lg-gold-soft: rgba(245, 179, 1, .18);
        /* kept names so page-specific styles keep working */
        --lg-teal: var(--lg-gold);
        --lg-teal-btn: var(--lg-gold);
        --lg-teal-btn-hover: var(--lg-gold-hover);
    }

    body.authentication-bg,
    .authentication-bg {
        background: var(--lg-bg) !important;
    }

    /* ---------- page ---------- */
    .lg-page {
        position: relative;
        min-height: 100vh;
        min-height: 100dvh;
        display: flex;
        flex-direction: column;
        padding: 24px 16px;
        overflow: hidden;
        background:
            radial-gradient(700px 480px at 50% -12%, rgba(37, 99, 235, .22), transparent 70%),
            radial-gradient(520px 380px at 50% 112%, rgba(245, 179, 1, .07), transparent 70%),
            var(--lg-bg);
        font-family: Inter, "Segoe UI", Roboto, Arial, sans-serif;
        color: var(--lg-text);
        -webkit-font-smoothing: antialiased;
    }

    /* faint football pitch lines: halfway line + centre circle */
    .lg-page::before {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: .5;
        background:
            radial-gradient(circle at 50% 50%, transparent 219px, rgba(148, 163, 184, .07) 220px, rgba(148, 163, 184, .07) 221px, transparent 222px),
            linear-gradient(90deg, transparent calc(50% - .5px), rgba(148, 163, 184, .06) calc(50% - .5px), rgba(148, 163, 184, .06) calc(50% + .5px), transparent calc(50% + .5px));
    }

    .lg-wrap {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 400px;
        margin: auto;
        /* centred when there is room, scrolls on short screens */
    }

    /* ---------- card ---------- */
    .lg-card {
        position: relative;
        overflow: hidden;
        background: linear-gradient(180deg, rgba(255, 255, 255, .025), transparent 140px), var(--lg-card);
        border: 1px solid var(--lg-line);
        border-radius: 16px;
        padding: 36px 32px 28px;
        box-shadow: 0 30px 70px rgba(1, 6, 20, .6);
    }

    .lg-card::before {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, var(--lg-gold) 30%, var(--lg-gold) 70%, transparent);
        opacity: .9;
    }

    .lg-head {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        margin-bottom: 26px;
    }

    .lg-logo {
        width: 76px;
        height: 76px;
        padding: 7px;
        border-radius: 50%;
        background: #fff;
        object-fit: contain;
        box-shadow: 0 0 0 4px var(--lg-gold-soft), 0 10px 24px rgba(0, 0, 0, .35);
        margin-bottom: 14px;
    }

    .lg-head .name {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: var(--lg-heading);
        line-height: 1.25;
        letter-spacing: .1px;
    }

    .lg-head .tag {
        margin: 4px 0 0;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: var(--lg-gold);
    }

    .lg-intro {
        text-align: center;
        margin-bottom: 22px;
    }

    .lg-title {
        margin: 0 0 4px;
        font-size: 21px;
        font-weight: 600;
        color: var(--lg-heading);
        letter-spacing: -.2px;
    }

    .lg-sub {
        margin: 0;
        font-size: 14px;
        color: var(--lg-muted);
    }

    /* ---------- messages ---------- */
    .lg-alert {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 10px 12px;
        margin-bottom: 18px;
        border-radius: 10px;
        font-size: 13.5px;
        line-height: 1.45;
    }

    .lg-alert svg {
        flex: none;
        margin-top: 1px;
    }

    .lg-alert.err {
        background: rgba(239, 68, 68, .10);
        border: 1px solid rgba(239, 68, 68, .35);
        color: #fca5a5;
    }

    .lg-alert.ok {
        background: rgba(34, 197, 94, .10);
        border: 1px solid rgba(34, 197, 94, .35);
        color: #86efac;
    }

    /* ---------- fields ---------- */
    .lg-field {
        margin-bottom: 16px;
    }

    .lg-field label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        font-weight: 500;
        color: #cbd5e1;
    }

    .lg-input {
        position: relative;
    }

    .lg-input input {
        width: 100%;
        height: 46px;
        padding: 0 44px 0 42px;
        font-size: 15px;
        color: var(--lg-text);
        background: var(--lg-field);
        border: 1px solid var(--lg-line-strong);
        border-radius: 10px;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
        -webkit-appearance: none;
        caret-color: var(--lg-teal);
    }

    .lg-input input::placeholder {
        color: var(--lg-faint);
    }

    .lg-input input:hover {
        border-color: rgba(148, 163, 184, .4);
    }

    .lg-input input:focus {
        border-color: var(--lg-gold);
        box-shadow: 0 0 0 3px var(--lg-gold-soft);
    }

    /* browser autofill: keep the dark field instead of the yellow fill */
    .lg-input input:-webkit-autofill,
    .lg-input input:-webkit-autofill:hover,
    .lg-input input:-webkit-autofill:focus {
        -webkit-text-fill-color: var(--lg-text);
        -webkit-box-shadow: 0 0 0 1000px var(--lg-field) inset;
        transition: background-color 9999s ease-out;
    }

    .lg-input input:-webkit-autofill:focus {
        -webkit-box-shadow: 0 0 0 1000px var(--lg-field) inset, 0 0 0 3px var(--lg-gold-soft);
    }

    .lg-input.bad input {
        border-color: rgba(239, 68, 68, .7);
    }

    .lg-input .lead {
        position: absolute;
        left: 13px;
        top: 50%;
        width: 18px;
        height: 18px;
        transform: translateY(-50%);
        stroke: var(--lg-faint);
        pointer-events: none;
    }

    .lg-input:focus-within .lead {
        stroke: var(--lg-teal);
    }

    .lg-input .eye {
        position: absolute;
        right: 5px;
        top: 50%;
        transform: translateY(-50%);
        width: 36px;
        height: 36px;
        border: 0;
        background: transparent;
        border-radius: 8px;
        display: grid;
        place-items: center;
        cursor: pointer;
        color: var(--lg-faint);
        transition: background .15s, color .15s;
    }

    .lg-input .eye:hover,
    .lg-input .eye.on {
        background: rgba(148, 163, 184, .12);
        color: var(--lg-text);
    }

    .lg-input .eye:focus-visible {
        outline: 2px solid var(--lg-teal);
        outline-offset: 1px;
    }

    .lg-input .eye svg {
        width: 18px;
        height: 18px;
        stroke: currentColor;
    }

    .lg-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 4px 0 22px;
    }

    .lg-check {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        font-size: 13.5px;
        color: #cbd5e1;
        cursor: pointer;
        user-select: none;
    }

    .lg-check input {
        width: 16px;
        height: 16px;
        accent-color: var(--lg-teal-btn);
        cursor: pointer;
    }

    /* ---------- button ---------- */
    .lg-btn {
        width: 100%;
        height: 46px;
        border: 0;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        color: #0b1b36;
        cursor: pointer;
        background: var(--lg-teal-btn);
        box-shadow: 0 8px 20px rgba(245, 179, 1, .18);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        transition: background .15s, transform .1s, opacity .15s;
    }

    .lg-btn:hover {
        background: var(--lg-teal-btn-hover);
    }

    .lg-btn:active {
        transform: translateY(1px);
    }

    .lg-btn:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px var(--lg-card), 0 0 0 5px var(--lg-teal);
    }

    .lg-btn:disabled {
        opacity: .7;
        cursor: wait;
    }

    .lg-spin {
        width: 16px;
        height: 16px;
        border: 2px solid rgba(11, 27, 54, .3);
        border-top-color: #0b1b36;
        border-radius: 50%;
        animation: lgspin .7s linear infinite;
    }

    @keyframes lgspin {
        to {
            transform: rotate(360deg);
        }
    }

    .lg-link {
        padding: 0;
        border: 0;
        background: none;
        color: var(--lg-teal);
        font: inherit;
        font-weight: 500;
        cursor: pointer;
    }

    .lg-link:hover {
        text-decoration: underline;
    }

    .lg-foot {
        margin: 20px 0 0;
        text-align: center;
        font-size: 12px;
        color: var(--lg-faint);
    }

    /* ---------- phones: same compact card, a little less padding ---------- */
    @media (max-width: 575.98px) {
        .lg-page {
            padding: 20px 16px;
        }

        .lg-page::before {
            display: none;
        }

        .lg-card {
            padding: 28px 20px 22px;
            border-radius: 14px;
        }

        .lg-head {
            margin-bottom: 20px;
        }

        .lg-logo {
            width: 64px;
            height: 64px;
            padding: 6px;
            margin-bottom: 12px;
        }

        .lg-head .name {
            font-size: 17px;
        }

        .lg-title {
            font-size: 19px;
        }

        /* 16px stops iPhone Safari zooming into the field */
        .lg-input input {
            font-size: 16px;
        }
    }
</style>
<?php /**PATH F:\Nexas\ms-academy\resources\views/auth/partials/auth-style.blade.php ENDPATH**/ ?>