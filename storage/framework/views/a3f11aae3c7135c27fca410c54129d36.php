
<?php if (! $__env->hasRenderedOnce('3dc0abda-f7b9-48db-a714-c073761a7ebc')): $__env->markAsRenderedOnce('3dc0abda-f7b9-48db-a714-c073761a7ebc'); ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <style>
        .flatpickr-input[readonly].ac-date {
            background-color: var(--bs-body-bg, #fff);
            cursor: text;
        }

        .flatpickr-input.ac-date:disabled,
        .flatpickr-input.ac-date.ac-locked {
            background-color: var(--bs-secondary-bg, #e9ecef);
            cursor: not-allowed;
        }

        .ac-date-wrap {
            position: relative;
        }

        .ac-date-wrap > iconify-icon {
            position: absolute;
            right: 11px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #94a3b8;
            pointer-events: none;
        }

        .ac-date-wrap .ac-date {
            padding-right: 36px;
        }

        /* bigger tap targets on phones */
        .flatpickr-calendar.ac-cal {
            font-size: 15px;
        }

        .flatpickr-calendar.ac-cal .flatpickr-monthDropdown-months {
            font-weight: 600;
        }

        .flatpickr-calendar.ac-cal .numInputWrapper input.cur-year {
            font-weight: 600;
        }

        .ac-cal-today {
            display: block;
            width: 100%;
            border: 0;
            border-top: 1px solid rgba(100, 116, 139, .15);
            background: transparent;
            padding: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #0f766e;
        }
    </style>
    <script>
        window.ACDate = (function() {
            // base calendar CSS goes FIRST in <head> so the theme's calendar colours still win
            if (!document.getElementById('flatpickr-base-css')) {
                var link = document.createElement('link');
                link.id = 'flatpickr-base-css';
                link.rel = 'stylesheet';
                link.href = 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css';
                document.head.insertBefore(link, document.head.firstChild);
            }

            function pad(n) {
                return (n < 10 ? '0' : '') + n;
            }

            function valid(y, m, d) {
                var dt = new Date(y, m - 1, d);
                return dt.getFullYear() === y && dt.getMonth() === m - 1 && dt.getDate() === d ? dt : undefined;
            }

            // what staff may type: 31/12/2015, 31-12-2015, 31.12.2015, 31122015, 2015-12-31, 31/12/15
            function parse(str) {
                str = String(str || '').trim();
                var m;
                if ((m = str.match(/^(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})$/))) return valid(+m[1], +m[2], +m[3]);
                if ((m = str.match(/^(\d{1,2})[-/. ](\d{1,2})[-/. ](\d{2}|\d{4})$/))) {
                    var y = +m[3];
                    if (m[3].length === 2) y += (y <= new Date().getFullYear() % 100 ? 2000 : 1900);
                    return valid(y, +m[2], +m[1]);
                }
                if ((m = str.match(/^(\d{2})(\d{2})(\d{4})$/))) return valid(+m[3], +m[2], +m[1]);
                return undefined;
            }

            function enhanceOne(input) {
                if (input._flatpickr || input.dataset.noPicker !== undefined) return;
                var isDob = input.dataset.picker === 'dob';
                input.type = 'text';

                var fp = flatpickr(input, {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    altInputClass: (input.className || 'form-control') + ' ac-date',
                    allowInput: true,
                    disableMobile: true, // same easy picker on phones (no tiny native wheel)
                    monthSelectorType: 'dropdown',
                    parseDate: function(s) {
                        return parse(s);
                    },
                    minDate: input.getAttribute('min') || null,
                    maxDate: input.getAttribute('max') || null,
                    onReady: function(sel, str, inst) {
                        inst.calendarContainer.classList.add('ac-cal');
                        inst.altInput.placeholder = input.getAttribute('placeholder') || 'dd/mm/yyyy';
                        inst.altInput.setAttribute('inputmode', 'numeric');
                        inst.altInput.setAttribute('autocomplete', 'off');
                        if (input.id) inst.altInput.setAttribute('aria-label', (document.querySelector('label[for="' + input.id + '"]') || {}).textContent || 'Date');

                        // "Today" shortcut (not for date of birth)
                        if (!isDob) {
                            var b = document.createElement('button');
                            b.type = 'button';
                            b.className = 'ac-cal-today';
                            b.textContent = 'Today';
                            b.addEventListener('click', function() {
                                inst.setDate(new Date(), true);
                                inst.close();
                            });
                            inst.calendarContainer.appendChild(b);
                        }

                        // calendar icon inside the field
                        var wrap = document.createElement('div');
                        wrap.className = 'ac-date-wrap';
                        inst.altInput.parentNode.insertBefore(wrap, inst.altInput);
                        wrap.appendChild(inst.altInput);
                        var ico = document.createElement('iconify-icon');
                        ico.setAttribute('icon', 'solar:calendar-outline');
                        wrap.appendChild(ico);
                    },
                    onOpen: function(sel, str, inst) {
                        // date of birth: start about 10 years back instead of this month
                        if (isDob && !sel.length) inst.jumpToDate(new Date(new Date().getFullYear() - 10, 0, 1));
                    }
                });

                // typed value is applied when the field loses focus or Enter is pressed
                fp.altInput.addEventListener('blur', function() {
                    var v = fp.altInput.value.trim();
                    if (v === '') {
                        if (fp.selectedDates.length) fp.clear();
                        return;
                    }
                    var d = parse(v),
                        cur = fp.selectedDates[0];
                    if (!d) fp.altInput.classList.add('is-invalid');
                    else if (!cur || cur.toDateString() !== d.toDateString()) fp.setDate(d, true);
                });
                fp.altInput.addEventListener('input', function() {
                    fp.altInput.classList.remove('is-invalid');
                });
            }

            function enhance(root) {
                if (typeof flatpickr === 'undefined') return; // no internet: plain browser date fields still work
                (root || document).querySelectorAll('input[type=date]').forEach(enhanceOne);
            }

            // set value / limits from page scripts (works with or without the picker)
            function set(input, value, o) {
                o = o || {};
                var fp = input._flatpickr;
                if (!fp) {
                    input.value = value || '';
                    if ('min' in o) input.min = o.min || '';
                    if ('max' in o) input.max = o.max || '';
                    if ('locked' in o) input.readOnly = !!o.locked;
                    return;
                }
                if ('min' in o) fp.set('minDate', o.min || null);
                if ('max' in o) fp.set('maxDate', o.max || null);
                if ('locked' in o) {
                    fp.set('clickOpens', !o.locked);
                    fp.altInput.readOnly = !!o.locked;
                    fp.altInput.classList.toggle('ac-locked', !!o.locked);
                }
                value ? fp.setDate(value, false) : fp.clear(false);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    enhance();
                });
            } else {
                enhance();
            }

            return {
                enhance: enhance,
                set: set,
                parse: parse
            };
        })();
    </script>
<?php endif; ?>
<?php /**PATH F:\Nexas\ms-academy\resources\views/partials/date-picker.blade.php ENDPATH**/ ?>