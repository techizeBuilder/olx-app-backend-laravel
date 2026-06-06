<?php $__env->startSection('title'); ?>
    <?php echo e(__('AdSense') . " " . __("Settings")); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-title'); ?>
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4><?php echo $__env->yieldContent('title'); ?></h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first"></div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <section class="section">
        <form class="create-form-without-reset" action="<?php echo e(route('settings.store')); ?>" method="post" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <div class="row d-flex mb-3">

                
                <div class="col-12 mt-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text"><?php echo e(__('Google AdSense Configuration')); ?></h6>
                            </div>

                            <div class="form-group row mt-3 align-items-center">
                                <label for="adsense_enabled" class="col-sm-3 col-form-label fw-semibold">
                                    <?php echo e(__('Enable AdSense')); ?>

                                </label>
                                <div class="col-sm-9">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="adsense_enabled" id="adsense_enabled" value="<?php echo e($settings['adsense_enabled'] ?? 0); ?>">
                                        <input class="form-check-input switch-input status-switch"
                                               type="checkbox"
                                               role="switch"
                                               id="switch_adsense_enabled"
                                               aria-label="switch_adsense_enabled"
                                               <?php echo e(isset($settings['adsense_enabled']) && $settings['adsense_enabled'] == '1' ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="switch_adsense_enabled">
                                            <?php echo e(isset($settings['adsense_enabled']) && $settings['adsense_enabled'] == '1' ? __('Enabled') : __('Disabled')); ?>

                                        </label>
                                    </div>
                                </div>
                            </div>

                            
                            <div id="adsense_mode_section" class="form-group row mt-3 align-items-center <?php echo e((!isset($settings['adsense_enabled']) || $settings['adsense_enabled'] != '1') ? 'd-none' : ''); ?>">
                                <label class="col-sm-3 col-form-label fw-semibold"><?php echo e(__('AdSense Mode')); ?></label>
                                <div class="col-sm-9">
                                    <div class="d-flex gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adsense_mode"
                                                   id="adsense_mode_auto" value="automatic"
                                                   <?php echo e(($settings['adsense_mode'] ?? 'automatic') == 'automatic' ? 'checked' : ''); ?>>
                                            <label class="form-check-label" for="adsense_mode_auto">
                                                <span class="fw-semibold"><?php echo e(__('Automatic')); ?></span>
                                                <br>
                                                <small class="text-muted"><?php echo e(__('Only Client ID required. Google auto-places ads.')); ?></small>
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adsense_mode"
                                                   id="adsense_mode_manual" value="manual"
                                                   <?php echo e(($settings['adsense_mode'] ?? '') == 'manual' ? 'checked' : ''); ?>>
                                            <label class="form-check-label" for="adsense_mode_manual">
                                                <span class="fw-semibold"><?php echo e(__('Manual')); ?></span>
                                                <br>
                                                <small class="text-muted"><?php echo e(__('Manually define ad slots for banner, vertical, and square.')); ?></small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div id="adsense_client_section" class="col-12 mt-4 <?php echo e((!isset($settings['adsense_enabled']) || $settings['adsense_enabled'] != '1') ? 'd-none' : ''); ?>">
                    <div class="card">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text"><?php echo e(__('Publisher Details')); ?></h6>
                            </div>

                            <div class="form-group row mt-3">
                                <label for="adsense_client_id" class="col-sm-3 col-form-label fw-semibold">
                                    <?php echo e(__('Client ID')); ?>

                                    <span class="text-danger">*</span>
                                </label>
                                <div class="col-sm-9">
                                    <input id="adsense_client_id"
                                           name="adsense_client_id"
                                           type="text"
                                           class="form-control"
                                           placeholder="<?php echo e(__('e.g. ca-pub-0000000000000000')); ?>"
                                           value="<?php echo e($settings['adsense_client_id'] ?? ''); ?>">
                                    <small class="text-muted"><?php echo e(__('Your Google AdSense Publisher ID (ca-pub-XXXXXXXXXXXXXXXX)')); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div id="adsense_manual_section" class="col-12 mt-4 <?php echo e((($settings['adsense_enabled'] ?? 0) != '1' || ($settings['adsense_mode'] ?? 'automatic') != 'manual') ? 'd-none' : ''); ?>">
                    <div class="card">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text"><?php echo e(__('Manual Ad Slot IDs')); ?></h6>
                            </div>

                            
                            <div class="form-group row mt-3">
                                <label for="adsense_banner_slot_id" class="col-sm-3 col-form-label fw-semibold">
                                    <?php echo e(__('Banner Slot ID')); ?>

                                </label>
                                <div class="col-sm-9">
                                    <input id="adsense_banner_slot_id"
                                           name="adsense_banner_slot_id"
                                           type="text"
                                           class="form-control"
                                           placeholder="<?php echo e(__('e.g. 1234567890')); ?>"
                                           value="<?php echo e($settings['adsense_banner_slot_id'] ?? ''); ?>">
                                    <small class="text-muted"><?php echo e(__('Ad slot ID for horizontal banner ads')); ?></small>
                                </div>
                            </div>

                            
                            <div class="form-group row mt-3">
                                <label for="adsense_vertical_slot_id" class="col-sm-3 col-form-label fw-semibold">
                                    <?php echo e(__('Vertical Slot ID')); ?>

                                </label>
                                <div class="col-sm-9">
                                    <input id="adsense_vertical_slot_id"
                                           name="adsense_vertical_slot_id"
                                           type="text"
                                           class="form-control"
                                           placeholder="<?php echo e(__('e.g. 0987654321')); ?>"
                                           value="<?php echo e($settings['adsense_vertical_slot_id'] ?? ''); ?>">
                                    <small class="text-muted"><?php echo e(__('Ad slot ID for vertical / skyscraper ads')); ?></small>
                                </div>
                            </div>

                            
                            <div class="form-group row mt-3">
                                <label for="adsense_square_slot_id" class="col-sm-3 col-form-label fw-semibold">
                                    <?php echo e(__('Square Slot ID')); ?>

                                </label>
                                <div class="col-sm-9">
                                    <input id="adsense_square_slot_id"
                                           name="adsense_square_slot_id"
                                           type="text"
                                           class="form-control"
                                           placeholder="<?php echo e(__('e.g. 1122334455')); ?>"
                                           value="<?php echo e($settings['adsense_square_slot_id'] ?? ''); ?>">
                                    <small class="text-muted"><?php echo e(__('Ad slot ID for square / rectangle ads')); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary me-1 mb-3"><?php echo e(__('Save')); ?></button>
            </div>
        </form>
    </section>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
    <script>
        $(document).ready(function () {

            // Toggle sections based on adsense_enabled switch
            $('#switch_adsense_enabled').on('change', function () {
                var isEnabled = $(this).is(':checked');
                $('#adsense_enabled').val(isEnabled ? 1 : 0);
                $(this).next('label').text(isEnabled ? '<?php echo e(__('Enabled')); ?>' : '<?php echo e(__('Disabled')); ?>');

                if (isEnabled) {
                    $('#adsense_mode_section').removeClass('d-none');
                    $('#adsense_client_section').removeClass('d-none');
                    // Show manual section only if manual mode is selected
                    if ($('#adsense_mode_manual').is(':checked')) {
                        $('#adsense_manual_section').removeClass('d-none');
                    }
                } else {
                    $('#adsense_mode_section').addClass('d-none');
                    $('#adsense_client_section').addClass('d-none');
                    $('#adsense_manual_section').addClass('d-none');
                }
            });

            // Toggle manual slot IDs section based on mode selection
            $('input[name="adsense_mode"]').on('change', function () {
                if ($(this).val() === 'manual') {
                    $('#adsense_manual_section').removeClass('d-none');
                } else {
                    $('#adsense_manual_section').addClass('d-none');
                }
            });

        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Admin Panel 2.12.0\Eclassify Version 2.12.0 Fresh Installation\resources\views/settings/adsense.blade.php ENDPATH**/ ?>