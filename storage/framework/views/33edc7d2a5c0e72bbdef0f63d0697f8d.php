
<?php $__env->startSection('title'); ?>
    <?php echo e(__('Create Categories')); ?>

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
        <div class="buttons">
            <a class="btn btn-primary" href="<?php echo e(route('category.index')); ?>">
                < <?php echo e(__('Back to All Categories')); ?> </a>
        </div>
        <div class="row">
            <form action="<?php echo e(route('category.store')); ?>" method="POST" data-parsley-validate enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header"><?php echo e(__('Add Category')); ?></div>

                        <div class="card-body mt-2">
                            <ul class="nav nav-tabs" id="langTabs" role="tablist">
                                <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?php if($key == 0): ?> active <?php endif; ?>"
                                            id="tab-<?php echo e($lang->id); ?>" data-bs-toggle="tab"
                                            data-bs-target="#lang-<?php echo e($lang->id); ?>" type="button" role="tab">
                                            <?php echo e($lang->name); ?>

                                        </button>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>

                            <div class="tab-content mt-3">
                                <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="tab-pane fade <?php if($key == 0): ?> show active <?php endif; ?>"
                                        id="lang-<?php echo e($lang->id); ?>" role="tabpanel">
                                        <input type="hidden" name="languages[]" value="<?php echo e($lang->id); ?>">

                                        <div class="form-group">
                                            <label><?php echo e(__('Name')); ?> (<?php echo e($lang->name); ?>)</label>
                                            <input type="text" name="name[<?php echo e($lang->id); ?>]" class="form-control"
                                                value=""
                                                data-parsley-maxlength="191"
                                                maxlength="191"
                                                data-parsley-maxlength-message="<?php echo e(__('Name cannot exceed 191 characters.')); ?>"
                                                <?php if($lang->id == 1): ?> data-parsley-required="true" <?php endif; ?>>
                                        </div>

                                        <?php if($lang->id == 1): ?>
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="col-md-12 form-group">
                                                        <label for="category_slug" class="form-label"><?php echo e(__('Slug')); ?>

                                                            <small><?php echo e(__('(English Only)')); ?></small></label>
                                                        <input type="text" name="slug" id="category_slug"
                                                            class="form-control" data-parsley-pattern="^[a-zA-Z0-9\-_]+$"
                                                            data-parsley-pattern-message="<?php echo e(__('Slug must be only English letters, numbers, hyphens (-) or underscores (_).')); ?>"
                                                            placeholder="auto-generated if blank">
                                                        <label>
                                                            <small
                                                                class="text-danger"><?php echo e(__('Note: Slug must be in English letters, numbers, hyphens (-) or underscores (_). No spaces or special characters.')); ?></small>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="col-md-12 form-group">
                                                        <label for="p_category"
                                                            class="form-label"><?php echo e(__('Parent Category')); ?></label>
                                                        <select name="parent_category_id" id="p_category"
                                                            class="form-select form-control select2"
                                                            data-placeholder="<?php echo e(__('Select Category')); ?>">
                                                            <option value=""><?php echo e(__('Select a Category')); ?></option>
                                                            <?php echo $__env->make('category.dropdowntree', [
                                                                'categories' => $categories,
                                                            ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="col-md-12 form-group mandatory">
                                                        <label for="Field Name"
                                                            class="mandatory form-label"><?php echo e(__('Image')); ?></label>
                                                        <input type="file" name="image" id="image"
                                                            class="form-control" data-parsley-required="true"
                                                            accept=".jpg,.jpeg,.png">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="row mt-3">
                                                        <div class="col-md-3">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="status" id="status"
                                                                    value="0">
                                                                <input class="form-check-input status-switch"
                                                                    type="checkbox" role="switch" id="statusSwitch">
                                                                <label class="form-check-label"
                                                                    for="statusSwitch"><?php echo e(__('Active')); ?></label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="is_job_category"
                                                                    id="is_job_category" value="0">
                                                                <input class="form-check-input status-switch"
                                                                    type="checkbox" role="switch" id="jobCategorySwitch">
                                                                <label class="form-check-label"
                                                                    for="jobCategorySwitch"><?php echo e(__('Job Category')); ?></label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="price_optional"
                                                                    id="price_optional" value="0">
                                                                <input class="form-check-input status-switch"
                                                                    type="checkbox" role="switch"
                                                                    id="priceOptionalSwitch">
                                                                <label class="form-check-label"
                                                                    for="priceOptionalSwitch"><?php echo e(__('Price Optional')); ?></label>
                                                            </div>
                                                        </div>
                                                        
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php echo $__env->make('components.seo-fields', ['lang' => $lang, 'seoTranslations' => []], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 text-end">
                        <input type="submit" class="btn btn-primary" value="<?php echo e(__('Save and Back')); ?>">
                    </div>
                </div>
            </form>
        </div>
    </section>
<?php $__env->stopSection(); ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form[data-parsley-validate]');
        if (!form) return;

        const submitBtn = form.querySelector('input[type="submit"], button[type="submit"]');
        form.addEventListener('submit', function(e) {
            // Use Parsley to check validity if initialized
            if (typeof $(form).parsley === 'function') {
                if (!$(form).parsley().isValid()) {
                    // If invalid, do NOT disable the button, allow user to correct form
                    return;
                }
            }
            // Disable submit button on valid submission
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.value = '<?php echo e(__('Saving...')); ?>';
            }
        });
    });

    // Auto-switch to the tab containing the first Parsley validation error
    $(document).ready(function () {
        $('form[data-parsley-validate]').parsley().on('form:error', function () {
            // Find all fields that failed validation
            this.fields.forEach(function (field) {
                if (!field.isValid()) {
                    var $field = $(field.element);
                    // Walk up to find the parent tab-pane
                    var $tabPane = $field.closest('.tab-pane');
                    if ($tabPane.length && !$tabPane.hasClass('active')) {
                        var paneId = $tabPane.attr('id');
                        // Find the tab button that targets this pane
                        var $tabBtn = $('[data-bs-target="#' + paneId + '"]');
                        if ($tabBtn.length) {
                            // Use Bootstrap's Tab API to switch
                            var tabInstance = new bootstrap.Tab($tabBtn[0]);
                            tabInstance.show();
                        }
                        // Stop after switching to the first offending tab
                        return false;
                    }
                }
            });
        });
    });
</script>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\OLX\resources\views/category/create.blade.php ENDPATH**/ ?>