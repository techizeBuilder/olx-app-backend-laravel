<script>
    (function () {
        const $form = $('#banner-form');
        if (!$form.length) {
            return;
        }

        const LAST_STEP = 4;
        let step = 1;

        const label = {
            website: @json(__('Website')), app: @json(__('App')),
            home: @json(__('Home Page')), details: @json(__('Details Page')), listing: @json(__('Listing Page')),
            single: @json(__('Single Banner')), dual: @json(__('Dual Banner'))
        };

        /* ---------- Step navigation ---------- */

        function showStep(n) {
            step = n;
            $('.banner-step').addClass('d-none').filter('[data-step="' + n + '"]').removeClass('d-none');
            $('#banner-steps .nav-link').removeClass('active')
                .filter('[data-step="' + n + '"]').addClass('active');

            $('#btn-prev').toggleClass('d-none', n === 1);
            $('#btn-next').toggleClass('d-none', n === LAST_STEP);
            $('#btn-submit').toggleClass('d-none', n !== LAST_STEP);

            if (n === 3) {
                renderSummary();
            }
            refreshNext();
        }

        // A step is complete when its required inputs are filled.
        function stepIsValid(n) {
            if (n === 1) {
                return !!$('input[name=platform]:checked').val() && !!$('input[name=page]:checked').val();
            }
            if (n === 2) {
                return !!$('input[name=layout]:checked').val();
            }
            if (n === 3) {
                let ok = true;
                visibleSlots().each(function () {
                    const $slot = $(this);
                    const hasImage = $slot.find('.banner-image-input')[0].files.length > 0
                        || $slot.data('has-image') === 1;
                    if (!hasImage) {
                        ok = false;
                        return;
                    }
                    const type = $slot.find('.banner-ad-type').val();
                    if (type === 'category' && !$slot.find('select[name*="[category_id]"]').val()) ok = false;
                    if (type === 'advertisement' && !$slot.find('select[name*="[item_id]"]').val()) ok = false;
                    if (type === 'external_link' && !$slot.find('input[name*="[external_link]"]').val().trim()) ok = false;
                });
                return ok;
            }
            return true;
        }

        function refreshNext() {
            const valid = stepIsValid(step);
            $('#btn-next').prop('disabled', !valid);
            $('#btn-submit').prop('disabled', step === LAST_STEP ? false : true);
        }

        $('#btn-next').on('click', function () {
            if (stepIsValid(step) && step < LAST_STEP) {
                showStep(step + 1);
            }
        });

        $('#btn-prev').on('click', function () {
            if (step > 1) {
                showStep(step - 1);
            }
        });

        /* ---------- Step 1 : platform reveals page ---------- */

        $('input[name=platform]').on('change', function () {
            $('#page-block').removeClass('d-none');
            refreshNext();
        });
        $('input[name=page]').on('change', refreshNext);

        /* ---------- Step 2 : dual layout adds a second slot ---------- */

        function visibleSlots() {
            return $('.banner-slot').filter(':visible');
        }

        $('input[name=layout]').on('change', function () {
            const dual = $(this).val() === 'dual';
            $('.banner-slot[data-index=1]').toggle(dual);
            refreshNext();
        });

        /* ---------- Step 3 : ad type reveals its target field ---------- */

        $(document).on('change', '.banner-ad-type', function () {
            const $slot = $(this).closest('.banner-slot');
            const type = $(this).val();

            $slot.find('.target-field').addClass('d-none');
            if (type !== 'only_banner') {
                $slot.find('.target-' + type).removeClass('d-none');
            }
            refreshNext();
        });

        $(document).on('change keyup', '.banner-slot input, .banner-slot select', refreshNext);

        // Preview the chosen image.
        $(document).on('change', '.banner-image-input', function () {
            const $slot = $(this).closest('.banner-slot');
            const $preview = $slot.find('.banner-preview');
            const file = this.files[0];

            if (file) {
                $preview.attr('src', URL.createObjectURL(file)).removeClass('d-none');
                $slot.data('has-image', 1);
            }
            refreshNext();
        });

        function renderSummary() {
            const platform = $('input[name=platform]:checked').val();
            const page = $('input[name=page]:checked').val();
            const layout = $('input[name=layout]:checked').val();

            $('#banner-summary').html(
                summaryCell(@json(__('Platform')), label[platform]) +
                summaryCell(@json(__('Page')), label[page]) +
                summaryCell(@json(__('Banner Layout')), label[layout])
            );
        }

        function summaryCell(title, value) {
            return '<div><div class="text-muted small">' + title + '</div><div class="fw-bold">' + (value || '-') + '</div></div>';
        }

        /* ---------- Step 4 : placement ---------- */

        const $placement = $('#placement-list');
        if ($placement.length && $.fn.sortable) {
            $placement.sortable({
                items: 'li[data-banner=1]',   // only the banner row is draggable
                axis: 'y',
                cancel: 'li:not([data-banner=1])',
                update: syncSequence
            });
        }

        function syncSequence() {
            const position = $placement.find('li').index($placement.find('li[data-banner=1]')) + 1;
            $('#banner-sequence').val(position);
            $('#placement-position').text(position);
        }
        syncSequence();

        showStep(1);
    })();
</script>
