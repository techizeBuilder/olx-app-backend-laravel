

<?php $__env->startSection('title'); ?>
    <?php echo e(__("Seller Verifications")); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-title'); ?>
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4><?php echo $__env->yieldContent('title'); ?></h4>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <section class="section">
        <form action="<?php echo e(route('seller-verification.store')); ?>" method="POST" class="create-form" data-success-function="afterCustomFieldCreation" data-parsley-validate enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header"><?php echo e(__("Create Seller Verification")); ?></div>
                        <div class="card-body mt-2">
                            <ul class="nav nav-tabs" id="langTabs" role="tablist">
                                    <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link <?php if($key == 0): ?> active <?php endif; ?>" id="tab-<?php echo e($lang->id); ?>" data-bs-toggle="tab" data-bs-target="#lang-<?php echo e($lang->id); ?>" type="button" role="tab">
                                                <?php echo e($lang->name); ?>

                                            </button>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>

                                <div class="tab-content mt-3">
                                    <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="tab-pane fade <?php if($key == 0): ?> show active <?php endif; ?>" id="lang-<?php echo e($lang->id); ?>" role="tabpanel">
                                            <input type="hidden" name="languages[]" value="<?php echo e($lang->id); ?>">

                                            <div class="form-group">
                                                <label><?php echo e(__('Field Name')); ?> (<?php echo e($lang->name); ?>)</label>
                                                <input type="text" name="name[<?php echo e($lang->id); ?>]" class="form-control" <?php if($lang->id != 1): ?> required <?php endif; ?>>
                                            </div>

                                            <?php if($lang->id == 1): ?>
                                                
                                                <div class="form-group">
                                                    <label><?php echo e(__('Field Type')); ?></label>
                                                    <select name="type" class="form-control" required>
                                                        <option value="number"><?php echo e(__("Number Input")); ?></option>
                                                        <option value="textbox"><?php echo e(__("Text Input")); ?></option>
                                                        <option value="fileinput"><?php echo e(__("File Input")); ?></option>
                                                        <option value="radio"><?php echo e(__("Radio")); ?></option>
                                                        <option value="dropdown"><?php echo e(__("Dropdown")); ?></option>
                                                        <option value="checkbox"><?php echo e(__("Checkboxes")); ?></option>
                                                    </select>
                                                </div>

                                                
                                                <div class="row">
                                                    <div class="col-md-6 form-group min-max-fields">
                                                        <label><?php echo e(__('Field Length (Min)')); ?></label>
                                                        <input type="number" name="min_length" class="form-control">
                                                    </div>
                                                    <div class="col-md-6 form-group min-max-fields">
                                                        <label><?php echo e(__('Field Length (Max)')); ?></label>
                                                        <input type="number" name="max_length" class="form-control">
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-info mt-2">
                                                    <?php echo e(__('Field type, min/max length, required and status can only be set in English.')); ?>

                                                </div>
                                            <?php endif; ?>

                                            
                                            <div class="form-group">
                                                <label><?php echo e(__('Field Values')); ?> (<?php echo e($lang->name); ?>)</label>
                                                <select name="values[<?php echo e($lang->id); ?>][]" data-tags="true" data-placeholder="<?php echo e(__("Select an option")); ?>" data-allow-clear="true" class="select2 w-100 full-width-select2" multiple="multiple" <?php if($lang->id == 1): ?> required <?php endif; ?>></select>
                                                <?php if($lang->id != 1): ?>
                                                    <small class="text-muted"><?php echo e(__('This will be used for translatable field types only.')); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <div class="row">
                                <div class="col-md-6 form-group mandatory">
                                    <div class="form-check form-switch  ">
                                        <input type="hidden" name="is_required" id="required" value="0">
                                        <input class="form-check-input status-switch" type="checkbox" role="switch" aria-label="required"><?php echo e(__('Required')); ?>

                                        <label class="form-check-label" for="required"></label>
                                    </div>
                                </div>
                                <div class="col-md-6 form-group mandatory">
                                    <div class="form-check form-switch  ">
                                        <input type="hidden" name="status" id="status" value="1">
                                        <input class="form-check-input status-switch" type="checkbox" role="switch" aria-label="status" checked><?php echo e(__('Active')); ?>

                                        <label class="form-check-label" for="status"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <input type="submit" class="btn btn-primary" value="<?php echo e(__("Save and Back")); ?>">
                </div>
            </div>
        </form>
    </section>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
    <script>
       function updateVerificationFieldUI() {
        const type = $('select[name="type"]').val();
        const valuesTypes = ['radio', 'dropdown', 'checkbox'];

        // Loop through each language tab
        $('.tab-pane').each(function () {
            const $tab = $(this);
            const langId = $tab.attr('id').replace('lang-', '');

            const $fieldValues = $tab.find('select[name^="values"]')
                                     .closest('.form-group');

            const $minMaxGroup = $tab.find('.min-max-fields');

            if (valuesTypes.includes(type)) {
                $fieldValues.show();
                $minMaxGroup.hide();
            } else if (type === 'fileinput') {
                $fieldValues.hide();
                $minMaxGroup.hide();
            } else {
                $fieldValues.hide();
                $minMaxGroup.show();
            }
        });
    }

    $(document).ready(function () {
        updateVerificationFieldUI(); // Run on load

        $(document).on('change', 'select[name="type"]', function () {
            updateVerificationFieldUI(); // Run on change
        });
    });

        function afterCustomFieldCreation() {
            setTimeout(function () {
                window.location.href = "<?php echo e(route('seller-verification.verification-field')); ?>";
            }, 1000)
        }
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\OLX\resources\views/seller-verification/create.blade.php ENDPATH**/ ?>