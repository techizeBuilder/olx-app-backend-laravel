
<?php $__env->startSection('title'); ?>
    <?php echo e(__("Edit Categories")); ?>

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
            <a class="btn btn-primary" href="<?php echo e(route('category.index')); ?>">< <?php echo e(__("Back to All Categories")); ?> </a>
        </div>
        <div class="row">
            <form action="<?php echo e(route('category.update', $category_data->id)); ?>" method="POST" data-parsley-validate enctype="multipart/form-data">
                <?php echo method_field('PUT'); ?>
                <?php echo csrf_field(); ?>
                <input type="hidden" name="edit_data" value=<?php echo e($category_data->id); ?>>
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header"><?php echo e(__("Edit Categories")); ?></div>
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
                                            <label><?php echo e(__('Name')); ?> (<?php echo e($lang->name); ?>)</label>
                                            <input type="text" 
                                                name="name[<?php echo e($lang->id); ?>]" 
                                                class="form-control" 
                                                value="<?php echo e($translations[$lang->id]['name'] ?? ''); ?>"
                                                data-parsley-maxlength="191"
                                                maxlength="191"
                                                data-parsley-maxlength-message="<?php echo e(__('Name cannot exceed 191 characters.')); ?>"
                                                <?php if($lang->id == 1): ?> data-parsley-required="true" <?php endif; ?>>
                                        </div>

                                        <?php echo $__env->make('components.seo-fields', ['lang' => $lang, 'seoTranslations' => $seoTranslations ?? []], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

                                        <?php if($lang->id == 1): ?>
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="col-md-12 form-group mandatory">
                                                        <label for="category_slug" class="form-label"><?php echo e(__('Slug')); ?> <small><?php echo e(__('(English Only)')); ?></small></label>
                                                        <input type="text" name="slug" id="category_slug" class="form-control" data-parsley-pattern="^[a-zA-Z0-9\-_]+$"
                                                            data-parsley-pattern-message="<?php echo e(__('Slug must be only English letters, numbers, hyphens (-) or underscores (_).')); ?>" value="<?php echo e($category_data->slug); ?>">
                                                        <label>
                                                            <small class="text-danger"><?php echo e(__('Note: Slug must be in English letters, numbers, hyphens (-) or underscores (_). No spaces or special characters.')); ?></small>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="col-md-12 form-group mandatory">
                                                        <label for="p_category" class="mandatory form-label"><?php echo e(__('Parent Category')); ?></label>
                                                        <select name="parent_category_id" class="form-select form-control select2" id="p_category" data-placeholder="<?php echo e(__('Select Category')); ?>">
                                                            <?php if(isset($parent_category_data) && $parent_category_data->id): ?>
                                                                <option value="<?php echo e($parent_category_data->id); ?>" id="default_opt" selected>
                                                                    <?php echo e($parent_category == '' ? 'Root' : $parent_category); ?>

                                                                </option>
                                                            <?php else: ?>
                                                                <option value=""><?php echo e(__('Select Category')); ?></option>
                                                            <?php endif; ?>
                                                            <?php echo $__env->make('category.dropdowntree', ['categories' => $categories], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="col-md-12 form-group mandatory">
                                                        <label for="Field Name" class="mandatory form-label"><?php echo e(__('Image')); ?></label>
                                                        <div class="cs_field_img">
                                                            <input type="file" name="image" class="image" style="display: none" accept=" .jpg, .jpeg, .png, .svg">
                                                            <img src="<?php echo e(empty($category_data->image) ? asset('assets/img_placeholder.jpeg') : $category_data->image); ?>" alt="" class="img preview-image" id="">
                                                            <div class='img_input'><?php echo e(__("Browse File")); ?></div>
                                                        </div>
                                                        <div class="input_hint"> <?php echo e(__("Icon (use 256 x 256 size for better view)")); ?></div>
                                                        <div class="img_error" style="color:#DC3545;"></div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="row mt-3">
                                                        <div class="col-md-3">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="status" id="status" value="<?php echo e($category_data->status); ?>">
                                                                <input class="form-check-input status-switch" type="checkbox" role="switch" aria-label="status" data-parsley-excluded="true" <?php echo e($category_data->status == 1 ? 'checked' : ''); ?>>
                                                                <label class="form-check-label" for="status"><?php echo e(__('Active')); ?></label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="is_job_category" value="0">
                                                                <input
                                                                    class="form-check-input"
                                                                    type="checkbox"
                                                                    role="switch"
                                                                    name="is_job_category"
                                                                    id="job_category_switch"
                                                                    value="1"
                                                                    <?php echo e($category_data->is_job_category == 1 ? 'checked' : ''); ?>

                                                                >
                                                                <label class="form-check-label" for="job_category_switch"><?php echo e(__('Job Category')); ?></label>
                                                            </div>
                                                            <div id="job_category_warning" class="alert alert-warning mt-2 p-2" style="display:none; font-size:0.85rem;">
                                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                                <?php echo e(__('Warning: Changing the Job Category setting will affect all items linked to this category. Please update their prices accordingly.')); ?>

                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="price_optional" value="0">
                                                                <input
                                                                    class="form-check-input"
                                                                    type="checkbox"
                                                                    role="switch"
                                                                    name="price_optional"
                                                                    id="price_optional_switch"
                                                                    value="1"
                                                                    <?php echo e($category_data->price_optional == 1 ? 'checked' : ''); ?>

                                                                >
                                                                <label class="form-check-label" for="price_optional_switch"><?php echo e(__('Price Optional')); ?></label>
                                                            </div>
                                                            <div id="price_optional_warning" class="alert alert-warning mt-2 p-2" style="display:none; font-size:0.85rem;">
                                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                                <?php echo e(__('Warning: Changing the Price Optional setting will affect all items linked to this category. Please update their prices accordingly.')); ?>

                                                            </div>
                                                        </div>
                                                          
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 text-end">
                        <input type="submit" class="btn btn-primary" value="<?php echo e(__("Save and Back")); ?>">
                    </div>
                </div>
            </form>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script>
    document.getElementById('job_category_switch').addEventListener('change', function () {
        document.getElementById('job_category_warning').style.display = 'block';
    });
    document.getElementById('price_optional_switch').addEventListener('change', function () {
        document.getElementById('price_optional_warning').style.display = 'block';
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
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\OLX\resources\views/category/edit.blade.php ENDPATH**/ ?>